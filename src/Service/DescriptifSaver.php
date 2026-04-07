<?php

namespace App\Service;

use App\Constant\TypeChamp;
use App\Entity\Champ;
use App\Entity\Dossier;
use App\Entity\DossierDisplayRubrique;
use App\Entity\Etablissement;
use App\Entity\EtablissementDisplayRubrique;
use App\Entity\Rubrique;
use App\Entity\Valeur;
use App\Repository\ChampRepository;
use App\Repository\DossierDisplayRubriqueRepository;
use App\Repository\EtablissementDisplayRubriqueRepository;
use App\Repository\RubriqueRepository;
use App\Repository\ValeurRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Sauvegarde les valeurs des formulaires personnalisés (rubriques/champs) depuis les données POST.
 *
 * Convention de nommage des inputs POST (identique au legacy Zend pour iso-fonctionnalité) :
 *   - `champ-{champId}`                                      : champ simple ou enfant non-tableau (idx=null)
 *   - `valeur-{idx}-{parentId}-{champId}-{valeurId}`         : ligne de tableau existante
 *   - `valeur-{timestamp}-{parentId}-{champId}-NULL`         : nouvelle ligne de tableau (ajoutée via JS)
 *   - `afficher_rubrique-{rubriqueId}`                       : préférence d'affichage (0/1)
 *
 * Note : les inputs de tableau portent TOUJOURS le préfixe `valeur-` (STR_DATA dans le legacy).
 * Le préfixe `champ-` est réservé aux champs simples et enfants de groupes non-tableau.
 */
class DescriptifSaver
{
    /** @var ValeurRepository */
    private $valeurRepository;

    /** @var ChampRepository */
    private $champRepository;

    /** @var RubriqueRepository */
    private $rubriqueRepository;

    /** @var DossierDisplayRubriqueRepository */
    private $dossierDisplayRubriqueRepository;

    /** @var EtablissementDisplayRubriqueRepository */
    private $etablissementDisplayRubriqueRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        ValeurRepository $valeurRepository,
        ChampRepository $champRepository,
        RubriqueRepository $rubriqueRepository,
        DossierDisplayRubriqueRepository $dossierDisplayRubriqueRepository,
        EtablissementDisplayRubriqueRepository $etablissementDisplayRubriqueRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->valeurRepository = $valeurRepository;
        $this->champRepository = $champRepository;
        $this->rubriqueRepository = $rubriqueRepository;
        $this->dossierDisplayRubriqueRepository = $dossierDisplayRubriqueRepository;
        $this->etablissementDisplayRubriqueRepository = $etablissementDisplayRubriqueRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Sauvegarde les valeurs des formulaires personnalisés pour un dossier.
     *
     * @param array<string, mixed> $postData
     */
    public function saveForDossier(Dossier $dossier, array $postData): void
    {
        $existingValeurs = $this->valeurRepository->findByDossier($dossier);
        $this->doSave($dossier, $postData, $existingValeurs);
    }

    /**
     * Sauvegarde les valeurs des formulaires personnalisés pour un établissement.
     *
     * @param array<string, mixed> $postData
     */
    public function saveForEtablissement(Etablissement $etablissement, array $postData): void
    {
        $existingValeurs = $this->valeurRepository->findByEtablissement($etablissement);
        $this->doSave($etablissement, $postData, $existingValeurs);
    }

    /**
     * Logique commune de sauvegarde.
     *
     * @param Dossier|Etablissement $entity
     * @param array<string, mixed>  $postData
     * @param array<int, Valeur[]>  $existingValeurs indexées par champId
     */
    private function doSave($entity, array $postData, array $existingValeurs): void
    {
        $lookup = $this->buildLookup($existingValeurs);

        // Tracks IDs of existing Valeur tableau rows that were submitted (for delete detection)
        /** @var int[] $submittedExistingValeurIds */
        $submittedExistingValeurIds = [];

        // Groups new tableau rows by timestamp: [ts => ['parentId' => int, 'children' => [champId => value]]]
        /** @var array<string, array{parentId: int, children: array<int, mixed>}> $newTableauRows */
        $newTableauRows = [];

        foreach ($postData as $key => $value) {
            $key = (string) $key;

            if (0 === strpos($key, 'afficher_rubrique-')) {
                $rubriqueId = (int) substr($key, 18);
                $this->upsertDisplayPref($entity, $rubriqueId, '0' !== $this->toStr($value));
                continue;
            }

            if (0 === strpos($key, 'champ-')) {
                // Simple champ or non-tableau group child: champ-{champId}
                $parts = explode('-', $key);
                if (count($parts) < 2) {
                    continue;
                }

                $champId = (int) $parts[1];
                $this->upsertSimpleValeur($entity, $champId, null, $value, $lookup);
                continue;
            }

            if (0 === strpos($key, 'valeur-')) {
                $parts = explode('-', $key);
                // Both formats are 5 parts: valeur-{x}-{parentId}-{champId}-{valeurIdOrNULL}
                if (5 !== count($parts)) {
                    continue;
                }

                $champId = (int) $parts[3];

                if ('NULL' === $parts[4]) {
                    // New row: valeur-{timestamp}-{parentId}-{champId}-NULL
                    $ts = $parts[1];
                    $parentId = (int) $parts[2];

                    if (!isset($newTableauRows[$ts])) {
                        $newTableauRows[$ts] = ['parentId' => $parentId, 'children' => []];
                    }

                    $newTableauRows[$ts]['children'][$champId] = $value;
                } else {
                    // Existing row: valeur-{idx}-{parentId}-{champId}-{valeurId}
                    $valeurId = (int) $parts[4];
                    $submittedExistingValeurIds[] = $valeurId;
                    $this->updateExistingTableauValeur($valeurId, $champId, $value, $lookup);
                }
            }
        }

        // Insert new tableau rows, assigning sequential idx values per parent
        /** @var array<int, int> $nextIdxByParent tracks next available idx per parentId */
        $nextIdxByParent = [];
        foreach ($newTableauRows as $rowData) {
            $parentId = $rowData['parentId'];

            if (!isset($nextIdxByParent[$parentId])) {
                $nextIdxByParent[$parentId] = $this->computeNextIdx($existingValeurs, $rowData['children']);
            }

            $nextIdx = $nextIdxByParent[$parentId];

            foreach ($rowData['children'] as $champId => $value) {
                if ('' === $this->toStr($value)) {
                    continue;
                }

                $champ = $this->champRepository->find($champId);
                if (null === $champ) {
                    continue;
                }

                $valeur = $this->createValeur($champ, $nextIdx);
                $this->applyValueToValeur($valeur, $champ, $value);
                $entity->getValeurs()->add($valeur);
                $this->entityManager->persist($valeur);
            }

            ++$nextIdxByParent[$parentId];
        }

        // Delete existing tableau Valeurs not submitted (their rows were deleted by the user)
        $this->deleteRemovedTableauRows($entity, $existingValeurs, $submittedExistingValeurIds);

        $this->entityManager->flush();
    }

    /**
     * Met à jour une Valeur de tableau existante identifiée par son ID.
     * Si la valeur soumise est vide, supprime le Valeur.
     *
     * @param array<int, array<string, Valeur>> $lookup
     */
    private function updateExistingTableauValeur(int $valeurId, int $champId, $value, array $lookup): void
    {
        // Find by id in the lookup (any idx)
        foreach ($lookup[$champId] ?? [] as $existing) {
            if ($existing->getId() === $valeurId) {
                if ('' === $this->toStr($value)) {
                    // Will be cleaned up by deleteRemovedTableauRows if not in submitted set
                    return;
                }

                $champ = $existing->getChamp();
                if (null !== $champ) {
                    $this->applyValueToValeur($existing, $champ, $value);
                }

                return;
            }
        }
    }

    /**
     * Construit un index [champId][idxKey] = Valeur pour lookup O(1).
     * Les champs simples utilisent la clé 'null' pour l'idx.
     *
     * @param array<int, Valeur[]> $existingValeurs
     *
     * @return array<int, array<string, Valeur>>
     */
    private function buildLookup(array $existingValeurs): array
    {
        $lookup = [];

        foreach ($existingValeurs as $champId => $valeurs) {
            $innerIndex = [];
            foreach ($valeurs as $valeur) {
                $idxKey = null !== $valeur->getIdx() ? (string) $valeur->getIdx() : 'null';
                $innerIndex[$idxKey] = $valeur;
            }

            $lookup[$champId] = $innerIndex;
        }

        /* @var array<int, array<string, Valeur>> $lookup */
        return $lookup;
    }

    /**
     * Crée ou met à jour une Valeur pour un champ simple (idx=null).
     *
     * @param Dossier|Etablissement             $entity
     * @param array<int, array<string, Valeur>> $lookup
     */
    private function upsertSimpleValeur($entity, int $champId, ?int $idx, $value, array $lookup): void
    {
        $idxKey = null !== $idx ? (string) $idx : 'null';
        $existing = $lookup[$champId][$idxKey] ?? null;

        if ('' === $this->toStr($value)) {
            // Empty value: delete existing or skip
            if (null !== $existing) {
                $entity->getValeurs()->removeElement($existing);
                $this->entityManager->remove($existing);
            }

            return;
        }

        if (null !== $existing) {
            $champ = $existing->getChamp();
            if (null !== $champ) {
                $this->applyValueToValeur($existing, $champ, $value);
            }

            return;
        }

        // Create new Valeur
        $champ = $this->champRepository->find($champId);
        if (null === $champ) {
            return;
        }

        $valeur = $this->createValeur($champ, $idx);
        $this->applyValueToValeur($valeur, $champ, $value);
        $entity->getValeurs()->add($valeur);
        $this->entityManager->persist($valeur);
    }

    /**
     * Crée ou met à jour la préférence d'affichage d'une rubrique.
     *
     * @param Dossier|Etablissement $entity
     */
    private function upsertDisplayPref($entity, int $rubriqueId, bool $display): void
    {
        $rubrique = $this->rubriqueRepository->find($rubriqueId);
        if (null === $rubrique) {
            return;
        }

        if ($entity instanceof Dossier) {
            $this->upsertDossierDisplayPref($entity, $rubrique, $display);
        } elseif ($entity instanceof Etablissement) {
            $this->upsertEtablissementDisplayPref($entity, $rubrique, $display);
        }
    }

    private function upsertDossierDisplayPref(Dossier $dossier, Rubrique $rubrique, bool $display): void
    {
        $existing = $this->dossierDisplayRubriqueRepository->findOneBy([
            'dossier' => $dossier,
            'rubrique' => $rubrique,
        ]);

        if (null !== $existing) {
            $existing->setUserDisplay($display);

            return;
        }

        $pref = new DossierDisplayRubrique();
        $pref->setDossier($dossier);
        $pref->setRubrique($rubrique);
        $pref->setUserDisplay($display);

        $this->entityManager->persist($pref);
    }

    private function upsertEtablissementDisplayPref(Etablissement $etablissement, Rubrique $rubrique, bool $display): void
    {
        $existing = $this->etablissementDisplayRubriqueRepository->findOneBy([
            'etablissement' => $etablissement,
            'rubrique' => $rubrique,
        ]);

        if (null !== $existing) {
            $existing->setUserDisplay($display);

            return;
        }

        $pref = new EtablissementDisplayRubrique();
        $pref->setEtablissement($etablissement);
        $pref->setRubrique($rubrique);
        $pref->setUserDisplay($display);

        $this->entityManager->persist($pref);
    }

    /**
     * Supprime les Valeur de tableau dont les lignes ont été retirées du formulaire.
     * Seules les Valeur avec un idx non-null (= appartenant à un tableau) sont considérées.
     *
     * @param Dossier|Etablissement $entity
     * @param array<int, Valeur[]>  $existingValeurs
     * @param int[]                 $submittedExistingValeurIds IDs des Valeur soumises dans le POST
     */
    private function deleteRemovedTableauRows($entity, array $existingValeurs, array $submittedExistingValeurIds): void
    {
        foreach ($existingValeurs as $valeurs) {
            foreach ($valeurs as $valeur) {
                if (null === $valeur->getIdx()) {
                    continue;
                }

                $valeurId = $valeur->getId();
                if (null !== $valeurId && !in_array($valeurId, $submittedExistingValeurIds, true)) {
                    $entity->getValeurs()->removeElement($valeur);
                    $this->entityManager->remove($valeur);
                }
            }
        }
    }

    /**
     * Calcule le prochain idx disponible pour une nouvelle ligne de tableau.
     * Se base sur le max idx des champs enfants déjà présents dans $existingValeurs.
     *
     * @param array<int, Valeur[]> $existingValeurs
     * @param array<int, mixed>    $childChampIds   tableau [champId => value]
     */
    private function computeNextIdx(array $existingValeurs, array $childChampIds): int
    {
        $maxIdx = 0;

        foreach (array_keys($childChampIds) as $champId) {
            foreach ($existingValeurs[$champId] ?? [] as $valeur) {
                $idx = $valeur->getIdx();
                if (null !== $idx && $idx > $maxIdx) {
                    $maxIdx = $idx;
                }
            }
        }

        return $maxIdx + 1;
    }

    /**
     * Crée une nouvelle entité Valeur pour un champ et un idx donné.
     */
    private function createValeur(Champ $champ, ?int $idx): Valeur
    {
        $valeur = new Valeur();
        $valeur->setChamp($champ);
        $valeur->setIdx($idx);

        return $valeur;
    }

    /**
     * Applique la valeur brute du formulaire POST au bon champ de l'entité Valeur
     * selon le TypeChamp du champ.
     *
     * @param mixed $rawValue valeur brute du POST (toujours une string en PHP)
     */
    private function applyValueToValeur(Valeur $valeur, Champ $champ, $rawValue): void
    {
        $type = $champ->getType();
        $typeId = null !== $type ? $type->getId() : null;
        $strValue = $this->toStr($rawValue);

        switch ($typeId) {
            case TypeChamp::TEXT:
            case TypeChamp::SELECT:
                $valeur->setStr('' !== $strValue ? $strValue : null);
                break;

            case TypeChamp::TEXTAREA:
                $valeur->setLongStr('' !== $strValue ? $strValue : null);
                break;

            case TypeChamp::NUMBER:
                $valeur->setNumber('' !== $strValue ? (int) $strValue : null);
                break;

            case TypeChamp::CHECKBOX:
                $valeur->setCheckbox('1' === $strValue);
                break;

            case TypeChamp::DATE:
                $date = \DateTime::createFromFormat('d/m/Y', $strValue);
                $valeur->setDate($date instanceof \DateTime ? $date : null);
                break;
        }
    }

    /**
     * Convertit une valeur mixte en chaîne de manière sûre pour PHPStan niveau 10.
     */
    private function toStr($value): string
    {
        if (is_scalar($value) || null === $value) {
            return (string) ($value ?? '');
        }

        return '';
    }
}
