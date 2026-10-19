<?php

namespace App\Service\Descriptif;

use App\Constant\TypeChamp;
use App\Dto\Descriptif\ChampValueData;
use App\Dto\Descriptif\DescriptifFormData;
use App\Dto\Descriptif\TableauRowData;
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
use App\Repository\ValeurRepository;
use App\Service\Descriptif;
use App\ValueObject\RubriqueEtat;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Persiste un DescriptifFormData vers les entités Valeur en base de données.
 *
 * Remplace DescriptifSaver en travaillant avec des DTOs typés au lieu de données POST brutes.
 *
 * Stratégie idx pour les tableaux :
 * - L'idx d'une ligne est déterminé par sa POSITION dans le tableau lignes[] (1-based).
 * - Toutes les lignes (existantes et nouvelles) reçoivent leur idx recalculé à chaque save.
 * - Cela garantit la cohérence après un drag & drop (le JS renomme les indices CollectionType
 *   dans l'ordre affiché, le Persister normalise ensuite les idx en base).
 *
 * @phpstan-import-type RubriqueData from Descriptif
 */
final readonly class DescriptifFormPersister
{
    public function __construct(private ValeurRepository $valeurRepository, private ChampRepository $champRepository, private DossierDisplayRubriqueRepository $dossierDisplayRubriqueRepository, private EtablissementDisplayRubriqueRepository $etablissementDisplayRubriqueRepository, private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Persiste les valeurs d'un formulaire personnalisé pour un Dossier.
     *
     * @param list<RubriqueData> $rubriques
     */
    public function persistForDossier(
        Dossier $dossier,
        DescriptifFormData $formData,
        array $rubriques
    ): void {
        $existingValeurs = $this->valeurRepository->findByDossier($dossier);
        $this->doPersist($dossier, $formData, $rubriques, $existingValeurs);
    }

    /**
     * Persiste les valeurs d'un formulaire personnalisé pour un Établissement.
     *
     * @param list<RubriqueData> $rubriques
     */
    public function persistForEtablissement(
        Etablissement $etablissement,
        DescriptifFormData $formData,
        array $rubriques
    ): void {
        $existingValeurs = $this->valeurRepository->findByEtablissement($etablissement);
        $this->doPersist($etablissement, $formData, $rubriques, $existingValeurs);
    }

    /**
     * Logique commune de persistance.
     *
     * @param list<RubriqueData>   $rubriques
     * @param array<int, Valeur[]> $existingValeurs indexées par champId
     */
    private function doPersist(Dossier|Etablissement $entity, DescriptifFormData $formData, array $rubriques, array $existingValeurs): void
    {
        $lookup = $this->buildLookup($existingValeurs);
        /** @var int[] $touchedValeurIds IDs des Valeur tableau traitées (pour détection des suppressions) */
        $touchedValeurIds = [];

        foreach ($rubriques as $item) {
            /** @var Rubrique $rubrique */
            $rubrique = $item['rubrique'];
            $rubriqueId = (int) $rubrique->getId();
            $rubriqueData = $formData->getRubrique($rubriqueId);

            if (null === $rubriqueData) {
                continue;
            }

            $this->upsertPreferences($entity, $rubrique, $rubriqueData->getEtat());

            foreach ($rubriqueData->getChamps() as $champValueData) {
                $this->persistChampValue($entity, $champValueData, $lookup, $existingValeurs, $touchedValeurIds);
            }
        }

        // Restreindre la suppression aux champIds de l'onglet courant uniquement.
        // Sans ce filtre, les valeurs tableau des autres onglets seraient supprimées
        // car elles ne figurent pas dans $touchedValeurIds.
        $scopedChampIds = $this->collectScopedChampIds($rubriques);
        $this->deleteUntouchedTableauValeurs($entity, $existingValeurs, $touchedValeurIds, $scopedChampIds);

        $this->entityManager->flush();
    }

    /**
     * Persiste la valeur d'un champ (simple, tableau ou groupe).
     *
     * @param Dossier|Etablissement             $entity
     * @param array<int, array<string, Valeur>> $lookup
     * @param array<int, Valeur[]>              $existingValeurs
     * @param int[]                             $touchedValeurIds
     */
    private function persistChampValue(
        $entity,
        ChampValueData $champValueData,
        array $lookup,
        array $existingValeurs,
        array &$touchedValeurIds
    ): void {
        $champ = $this->champRepository->find($champValueData->getChampId());
        if (null === $champ) {
            return;
        }

        $type = $champ->getType();
        $typeId = $type?->getId();

        if (TypeChamp::PARENT === $typeId) {
            if ($champ->getTableau()) {
                $this->persistTableau($entity, $champValueData, $lookup, $touchedValeurIds);
            } else {
                $this->persistGroupe($entity, $champValueData, $lookup, $existingValeurs, $touchedValeurIds);
            }

            return;
        }

        $this->persistSimple($entity, $champValueData, $lookup);
    }

    /**
     * Persiste un champ simple (non-tableau, non-groupe).
     *
     * @param Dossier|Etablissement             $entity
     * @param array<int, array<string, Valeur>> $lookup
     */
    private function persistSimple($entity, ChampValueData $champValueData, array $lookup): void
    {
        $champId = $champValueData->getChampId();
        $existing = $lookup[$champId]['null'] ?? null;
        $value = $champValueData->getValue();

        if ($this->isEmptyValue($value)) {
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

        $champ = $this->champRepository->find($champId);
        if (null === $champ) {
            return;
        }

        $valeur = $this->createValeur($champ, null);
        $this->applyValueToValeur($valeur, $champ, $value);
        $entity->getValeurs()->add($valeur);
        $this->entityManager->persist($valeur);
    }

    /**
     * Persiste les lignes d'un champ PARENT+tableau.
     *
     * Affecte idx = position (1-based) pour toutes les lignes soumises.
     * Cela garantit la cohérence après drag & drop.
     *
     * @param Dossier|Etablissement             $entity
     * @param array<int, array<string, Valeur>> $lookup
     * @param int[]                             $touchedValeurIds
     */
    private function persistTableau($entity, ChampValueData $champValueData, array $lookup, array &$touchedValeurIds): void
    {
        $lignes = $champValueData->getLignes();

        foreach ($lignes as $position => $row) {
            // idx 1-based pour la compatibilité avec le legacy (ksort sur les lignes)
            $idx = $position + 1;
            $this->persistTableauRow($entity, $row, $idx, $lookup, $touchedValeurIds);
        }
    }

    /**
     * Persiste une ligne d'un tableau.
     *
     * @param Dossier|Etablissement             $entity
     * @param array<int, array<string, Valeur>> $lookup
     * @param int[]                             $touchedValeurIds
     */
    private function persistTableauRow(
        $entity,
        TableauRowData $row,
        int $idx,
        array $lookup,
        array &$touchedValeurIds
    ): void {
        foreach ($row->getCells() as $champId => $value) {
            /** @var bool|\DateTimeInterface|int|string|null $value */
            $valeurId = $row->getValeurId($champId);

            if (null !== $valeurId) {
                if ($this->isEmptyValue($value)) {
                    // Valeur existante vidée : ne pas ajouter aux touchedValeurIds
                    // → sera supprimée par deleteUntouchedTableauValeurs
                    continue;
                }

                // Ligne existante : mise à jour valeur ET idx (pour drag & drop)
                $existing = $lookup[$champId]['i'.$idx] ?? null;
                $existing ??= $this->findValeurById($lookup[$champId] ?? [], $valeurId);

                if (null !== $existing) {
                    $champ = $existing->getChamp();
                    if (null !== $champ) {
                        $this->applyValueToValeur($existing, $champ, $value);
                        $existing->setIdx($idx);
                    }

                    $touchedValeurIds[] = $valeurId;
                    continue;
                }
            }

            // Cellule vide sans Valeur existante : rien à faire
            if ($this->isEmptyValue($value)) {
                continue;
            }

            // Nouvelle cellule non vide
            $champ = $this->champRepository->find($champId);
            if (null === $champ) {
                continue;
            }

            $valeur = $this->createValeur($champ, $idx);
            $this->applyValueToValeur($valeur, $champ, $value);
            $entity->getValeurs()->add($valeur);
            $this->entityManager->persist($valeur);
            $touchedValeurIds[] = -1; // nouvelle, ne sera pas supprimée
        }
    }

    /**
     * Persiste un champ PARENT+groupe (sous-champs non-tableau).
     *
     * @param Dossier|Etablissement             $entity
     * @param array<int, array<string, Valeur>> $lookup
     * @param array<int, Valeur[]>              $existingValeurs
     * @param int[]                             $touchedValeurIds
     */
    private function persistGroupe(
        $entity,
        ChampValueData $champValueData,
        array $lookup,
        array $existingValeurs,
        array &$touchedValeurIds
    ): void {
        foreach ($champValueData->getSousChamps() as $sousChampData) {
            $this->persistChampValue($entity, $sousChampData, $lookup, $existingValeurs, $touchedValeurIds);
        }
    }

    /**
     * Applique la valeur typée d'un DTO au bon champ de l'entité Valeur.
     *
     * @param string|int|bool|\DateTimeInterface|null $value valeur typée depuis le DTO
     */
    private function applyValueToValeur(Valeur $valeur, Champ $champ, $value): void
    {
        $type = $champ->getType();
        $typeId = $type?->getId();

        switch ($typeId) {
            case TypeChamp::TEXT:
            case TypeChamp::SELECT:
                $valeur->setStr(is_string($value) && '' !== $value ? $value : null);
                break;

            case TypeChamp::TEXTAREA:
                $valeur->setLongStr(is_string($value) && '' !== $value ? $value : null);
                break;

            case TypeChamp::NUMBER:
                $valeur->setNumber(is_int($value) ? $value : null);
                break;

            case TypeChamp::CHECKBOX:
                $valeur->setCheckbox(is_bool($value) && $value);
                break;

            case TypeChamp::DATE:
                if ($value instanceof \DateTimeInterface) {
                    $valeur->setDate($value);
                } else {
                    $valeur->setDate(null);
                }

                break;
        }
    }

    /**
     * Crée ou met à jour les préférences (affichage + ouverture) d'une rubrique.
     * La Rubrique est passée directement depuis la boucle appelante (déjà chargée).
     */
    private function upsertPreferences(Dossier|Etablissement $entity, Rubrique $rubrique, RubriqueEtat $etat): void
    {
        if ($entity instanceof Dossier) {
            $this->upsertDossierPreferences($entity, $rubrique, $etat);
        } elseif ($entity instanceof Etablissement) {
            $this->upsertEtablissementPreferences($entity, $rubrique, $etat);
        }
    }

    private function upsertDossierPreferences(Dossier $dossier, Rubrique $rubrique, RubriqueEtat $etat): void
    {
        $existing = $this->dossierDisplayRubriqueRepository->findOneBy([
            'dossier' => $dossier,
            'rubrique' => $rubrique,
        ]);

        if (null !== $existing) {
            $existing->setUserDisplay($etat->isDisplay());
            $existing->setUserOpened($etat->isOpened());

            return;
        }

        $pref = new DossierDisplayRubrique();
        $pref->setDossier($dossier);
        $pref->setRubrique($rubrique);
        $pref->setUserDisplay($etat->isDisplay());
        $pref->setUserOpened($etat->isOpened());

        $this->entityManager->persist($pref);
    }

    private function upsertEtablissementPreferences(Etablissement $etablissement, Rubrique $rubrique, RubriqueEtat $etat): void
    {
        $existing = $this->etablissementDisplayRubriqueRepository->findOneBy([
            'etablissement' => $etablissement,
            'rubrique' => $rubrique,
        ]);

        if (null !== $existing) {
            $existing->setUserDisplay($etat->isDisplay());
            $existing->setUserOpened($etat->isOpened());

            return;
        }

        $pref = new EtablissementDisplayRubrique();
        $pref->setEtablissement($etablissement);
        $pref->setRubrique($rubrique);
        $pref->setUserDisplay($etat->isDisplay());
        $pref->setUserOpened($etat->isOpened());

        $this->entityManager->persist($pref);
    }

    /**
     * Supprime les Valeur de tableau existantes qui n'ont pas été soumises (lignes supprimées).
     * Seuls les champIds appartenant aux rubriques soumises ($scopedChampIds) sont concernés,
     * pour ne pas affecter les valeurs des autres onglets du formulaire personnalisé.
     *
     * @param array<int, Valeur[]> $existingValeurs
     * @param int[]                $touchedValeurIds
     * @param int[]                $scopedChampIds
     */
    private function deleteUntouchedTableauValeurs(Dossier|Etablissement $entity, array $existingValeurs, array $touchedValeurIds, array $scopedChampIds): void
    {
        foreach ($existingValeurs as $champId => $valeurs) {
            if (!in_array($champId, $scopedChampIds, true)) {
                // Ce champ appartient à un autre onglet : ne pas toucher ses valeurs
                continue;
            }

            foreach ($valeurs as $valeur) {
                if (null === $valeur->getIdx()) {
                    // Champ simple, non concerné
                    continue;
                }

                $valeurId = $valeur->getId();
                if (null !== $valeurId && !in_array($valeurId, $touchedValeurIds, true)) {
                    $entity->getValeurs()->removeElement($valeur);
                    $this->entityManager->remove($valeur);
                }
            }
        }
    }

    /**
     * Collecte tous les IDs de champs (parent + enfants directs) appartenant aux rubriques soumises.
     * Permet de restreindre la suppression des valeurs tableau aux champs de l'onglet courant.
     *
     * @param list<RubriqueData> $rubriques
     *
     * @return int[]
     */
    private function collectScopedChampIds(array $rubriques): array
    {
        $champIds = [];

        foreach ($rubriques as $item) {
            /** @var list<mixed> $champsData */
            $champsData = $item['champs'];
            foreach ($champsData as $champData) {
                /** @var array<string, mixed> $champData */
                /** @var Champ $champ */
                $champ = $champData['champ'];
                $id = $champ->getId();
                if (null !== $id) {
                    $champIds[] = $id;
                }

                foreach ($champ->getEnfants() as $enfant) {
                    $enfantId = $enfant->getId();
                    if (null !== $enfantId) {
                        $champIds[] = $enfantId;
                    }
                }
            }
        }

        return $champIds;
    }

    /**
     * Construit un index [champId][idxKey] = Valeur pour lookup O(1).
     * Les champs simples utilisent la clé 'null'. Les lignes de tableau utilisent (string) idx.
     *
     * @param array<int, Valeur[]> $existingValeurs
     *
     * @return array<int, array<string, Valeur>>
     */
    private function buildLookup(array $existingValeurs): array
    {
        /** @var array<int, array<string, Valeur>> $lookup */
        $lookup = [];

        foreach ($existingValeurs as $champId => $valeurs) {
            foreach ($valeurs as $valeur) {
                $idxKey = null !== $valeur->getIdx() ? 'i'.$valeur->getIdx() : 'null';
                $lookup[(int) $champId][$idxKey] = $valeur;
            }
        }

        return $lookup;
    }

    /**
     * Cherche une Valeur par son ID dans un sous-tableau du lookup.
     *
     * Utilisé lors du drag & drop : une ligne existante peut avoir un idx différent de sa nouvelle position.
     *
     * @param array<string, Valeur> $champLookup
     */
    private function findValeurById(array $champLookup, int $valeurId): ?Valeur
    {
        foreach ($champLookup as $valeur) {
            if ($valeur->getId() === $valeurId) {
                return $valeur;
            }
        }

        return null;
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
     * Détermine si une valeur est considérée comme vide pour la suppression.
     * Les checkboxes décochées (false) sont traitées comme vides : aucune Valeur
     * n'est créée/maintenue en base pour une checkbox non cochée.
     *
     * @param string|int|bool|\DateTimeInterface|null $value
     */
    private function isEmptyValue($value): bool
    {
        if (null === $value) {
            return true;
        }

        if (false === $value) {
            return true;
        }

        if (is_string($value)) {
            return '' === $value;
        }

        return false;
    }
}
