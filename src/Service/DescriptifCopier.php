<?php

namespace App\Service;

use App\Constant\TypeChamp;
use App\Entity\Champ;
use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Entity\Rubrique;
use App\Entity\Valeur;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Gère la copie bidirectionnelle des valeurs de formulaires personnalisés
 * entre un dossier et son établissement lié.
 *
 * Le matching se fait strictement par nom de rubrique et de champ.
 * Si un nom ne correspond pas entre source et cible, le champ est ignoré et loggué.
 *
 * @phpstan-import-type RubriqueData from Descriptif
 */
final class DescriptifCopier
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly LoggerInterface $logger)
    {
    }

    /**
     * Copie les valeurs du dossier vers l'établissement.
     *
     * @param list<RubriqueData> $sourceRubriques
     * @param list<RubriqueData> $targetRubriques
     */
    public function copyFromDossierToEtablissement(
        array $sourceRubriques,
        array $targetRubriques,
        Etablissement $etablissement
    ): void {
        $this->doCopy($sourceRubriques, $targetRubriques, $etablissement);
    }

    /**
     * Copie les valeurs de l'établissement vers le dossier.
     *
     * @param list<RubriqueData> $sourceRubriques
     * @param list<RubriqueData> $targetRubriques
     */
    public function copyFromEtablissementToDossier(
        array $sourceRubriques,
        array $targetRubriques,
        Dossier $dossier
    ): void {
        $this->doCopy($sourceRubriques, $targetRubriques, $dossier);
    }

    /**
     * Copie les valeurs d'un dossier source vers un dossier cible.
     *
     * @param list<RubriqueData> $sourceRubriques
     * @param list<RubriqueData> $targetRubriques
     */
    public function copyFromDossierToDossier(
        array $sourceRubriques,
        array $targetRubriques,
        Dossier $dossier
    ): void {
        $this->doCopy($sourceRubriques, $targetRubriques, $dossier);
    }

    /**
     * Logique de copie commune.
     *
     * Pour chaque rubrique source, cherche une rubrique cible de même nom.
     * Pour chaque champ correspondant, copie la valeur vers la cible.
     *
     * @param list<RubriqueData> $sourceRubriques
     * @param list<RubriqueData> $targetRubriques
     */
    private function doCopy(array $sourceRubriques, array $targetRubriques, Etablissement|Dossier $targetEntity): void
    {
        foreach ($sourceRubriques as $sourceItem) {
            /** @var Rubrique $sourceRubrique */
            $sourceRubrique = $sourceItem['rubrique'];
            $rubriqueNom = $sourceRubrique->getNom();
            if (null === $rubriqueNom) {
                continue;
            }

            $targetItem = $this->matchRubriqueByNom($targetRubriques, $rubriqueNom);
            if (null === $targetItem) {
                $this->logger->warning('DescriptifCopier : rubrique "{nom}" absente dans la cible, ignorée.', ['nom' => $rubriqueNom]);
                continue;
            }

            /** @var list<array<string, mixed>> $sourceChamps */
            $sourceChamps = $sourceItem['champs'];
            /** @var list<array<string, mixed>> $targetChamps */
            $targetChamps = $targetItem['champs'];

            foreach ($sourceChamps as $sourceChampData) {
                /** @var Champ $sourceChamp */
                $sourceChamp = $sourceChampData['champ'];
                $champNom = $sourceChamp->getNom();
                if (null === $champNom) {
                    continue;
                }

                $targetChampData = $this->matchChampByNom($targetChamps, $champNom);
                if (null === $targetChampData) {
                    $this->logger->warning('DescriptifCopier : champ "{nom}" absent dans la rubrique cible "{rubrique}", ignoré.', ['nom' => $champNom, 'rubrique' => $rubriqueNom]);
                    continue;
                }

                $type = $sourceChamp->getType();
                if (null !== $type && TypeChamp::PARENT === $type->getId()) {
                    if ($sourceChamp->getTableau()) {
                        $this->copyParentTableau($sourceChampData, $targetChampData, $targetEntity);
                    } else {
                        $this->copyParentNonTableau($sourceChampData, $targetChampData, $targetEntity);
                    }
                } else {
                    /** @var Valeur|null $sourceValeur */
                    $sourceValeur = $sourceChampData['valeur'] ?? null;
                    $this->copySimpleChamp($sourceValeur, $targetChampData, $targetEntity);
                }
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Copie un champ simple (non parent).
     * Si la source n'a pas de valeur, le champ est ignoré.
     *
     * @param array<string, mixed> $targetChampData
     */
    private function copySimpleChamp(?Valeur $sourceValeur, array $targetChampData, Dossier|Etablissement $targetEntity): void
    {
        if (null === $sourceValeur) {
            return;
        }

        /** @var Champ $targetChamp */
        $targetChamp = $targetChampData['champ'];
        /** @var Valeur|null $targetValeur */
        $targetValeur = $targetChampData['valeur'] ?? null;

        $this->writeValeur($sourceValeur, $targetChamp, $targetValeur, $targetEntity, null);
    }

    /**
     * Copie un champ parent non-tableau (groupe de sous-champs).
     * Les sous-champs sont matchés par nom.
     *
     * @param array<string, mixed> $sourceChampData
     * @param array<string, mixed> $targetChampData
     */
    private function copyParentNonTableau(array $sourceChampData, array $targetChampData, Dossier|Etablissement $targetEntity): void
    {
        /** @var list<array<string, mixed>> $sourceSousChamps */
        $sourceSousChamps = $sourceChampData['sous_champs'] ?? [];
        /** @var list<array<string, mixed>> $targetSousChamps */
        $targetSousChamps = $targetChampData['sous_champs'] ?? [];

        foreach ($sourceSousChamps as $sourceSousChamp) {
            /** @var Valeur|null $sourceValeur */
            $sourceValeur = $sourceSousChamp['valeur'] ?? null;
            if (null === $sourceValeur) {
                continue;
            }

            /** @var Champ $sourceEnfantChamp */
            $sourceEnfantChamp = $sourceSousChamp['champ'];
            $nom = $sourceEnfantChamp->getNom();
            if (null === $nom) {
                continue;
            }

            $targetSousChamp = $this->matchChampByNom($targetSousChamps, $nom);
            if (null === $targetSousChamp) {
                $this->logger->warning('DescriptifCopier : sous-champ "{nom}" absent dans le champ parent cible, ignoré.', ['nom' => $nom]);
                continue;
            }

            /** @var Champ $targetEnfantChamp */
            $targetEnfantChamp = $targetSousChamp['champ'];
            /** @var Valeur|null $targetValeur */
            $targetValeur = $targetSousChamp['valeur'] ?? null;
            $this->writeValeur($sourceValeur, $targetEnfantChamp, $targetValeur, $targetEntity, null);
        }
    }

    /**
     * Copie un champ parent tableau.
     * Les valeurs cibles existantes sont supprimées, puis les valeurs sources sont insérées.
     * Les colonnes sont matchées par nom d'enfant.
     *
     * @param array<string, mixed> $sourceChampData
     * @param array<string, mixed> $targetChampData
     */
    private function copyParentTableau(array $sourceChampData, array $targetChampData, Dossier|Etablissement $targetEntity): void
    {
        // Suppression des valeurs tableau existantes dans la cible
        /** @var list<array<int, Valeur>> $targetLignes */
        $targetLignes = $targetChampData['lignes'] ?? [];
        foreach ($targetLignes as $ligne) {
            foreach ($ligne as $valeur) {
                $targetEntity->getValeurs()->removeElement($valeur);
                // orphanRemoval n'est pas supporté par Doctrine sur les relations ManyToMany,
                // il faut donc supprimer explicitement l'entité Valeur. Chaque Valeur appartient
                // à un seul parent (Dossier ou Etablissement) ; le partage n'est pas possible
                // en pratique du fait de la logique de copie.
                $this->entityManager->remove($valeur);
            }
        }

        /** @var Champ $targetChamp */
        $targetChamp = $targetChampData['champ'];
        $targetEnfants = $targetChamp->getEnfants()->toArray();
        /** @var list<array<int, Valeur>> $sourceLignes */
        $sourceLignes = $sourceChampData['lignes'] ?? [];
        /** @var Champ $sourceChamp */
        $sourceChamp = $sourceChampData['champ'];
        // On séquence les lignes cibles à partir de 1 (indépendamment de l'idx source).
        // L'idx source n'a aucune signification dans la cible. On part de 1 (et non 0)
        // pour éviter toute collision avec idx=null qui est traité comme 0 à la lecture.
        $newIdx = 1;

        foreach ($sourceLignes as $sourceLigne) {
            foreach ($sourceLigne as $sourceChampId => $sourceValeur) {
                $sourceEnfant = $this->findEnfantById($sourceChamp, $sourceChampId);
                if (null === $sourceEnfant) {
                    continue;
                }

                $nom = $sourceEnfant->getNom();
                if (null === $nom) {
                    continue;
                }

                $targetEnfant = $this->matchChampByNomInArray($targetEnfants, $nom);
                if (null === $targetEnfant) {
                    $this->logger->warning('DescriptifCopier : colonne tableau "{nom}" absente dans le champ cible, ignorée.', ['nom' => $nom]);
                    continue;
                }

                $newValeur = $this->createValeur($targetEnfant, $newIdx);
                $this->applySourceToTarget($sourceValeur, $newValeur);
                $targetEntity->getValeurs()->add($newValeur);
                $this->entityManager->persist($newValeur);
            }

            ++$newIdx;
        }
    }

    /**
     * Copie tous les champs de valeur d'une Valeur source vers une Valeur cible.
     * L'idx n'est pas copié (il est géré séparément pour les tableaux).
     */
    private function applySourceToTarget(Valeur $source, Valeur $target): void
    {
        $target->setStr($source->getStr());
        $target->setLongStr($source->getLongStr());
        $target->setNumber($source->getNumber());
        $target->setCheckbox($source->getCheckbox());
        $target->setDate($source->getDate());
    }

    /**
     * Met à jour une Valeur existante ou en crée une nouvelle pour écrire la valeur source.
     */
    private function writeValeur(Valeur $source, Champ $targetChamp, ?Valeur $existing, Dossier|Etablissement $targetEntity, ?int $idx): void
    {
        if (null !== $existing) {
            $this->applySourceToTarget($source, $existing);
        } else {
            $newValeur = $this->createValeur($targetChamp, $idx);
            $this->applySourceToTarget($source, $newValeur);
            $targetEntity->getValeurs()->add($newValeur);
            $this->entityManager->persist($newValeur);
        }
    }

    /**
     * Crée une nouvelle Valeur liée à un champ avec un idx optionnel.
     */
    private function createValeur(Champ $champ, ?int $idx): Valeur
    {
        $valeur = new Valeur();
        $valeur->setChamp($champ);
        $valeur->setIdx($idx);

        return $valeur;
    }

    /**
     * Trouve un enfant dans la collection du champ parent par son ID.
     */
    private function findEnfantById(Champ $parentChamp, int $champId): ?Champ
    {
        foreach ($parentChamp->getEnfants() as $enfant) {
            if ($enfant->getId() === $champId) {
                return $enfant;
            }
        }

        return null;
    }

    /**
     * Cherche un élément rubrique par nom dans la liste des rubriques.
     *
     * @param list<RubriqueData> $rubriques
     *
     * @return RubriqueData|null
     */
    public function matchRubriqueByNom(array $rubriques, string $nom): ?array
    {
        $normalizedNom = $this->normalizeString($nom);

        foreach ($rubriques as $item) {
            /** @var Rubrique $rubrique */
            $rubrique = $item['rubrique'];
            if ($this->normalizeString((string) $rubrique->getNom()) === $normalizedNom) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Cherche un champ data par nom dans une liste de champs data.
     *
     * @param list<array<string, mixed>> $champs
     *
     * @return array<string, mixed>|null
     */
    public function matchChampByNom(array $champs, string $nom): ?array
    {
        $normalizedNom = $this->normalizeString($nom);

        foreach ($champs as $champData) {
            /** @var Champ $champ */
            $champ = $champData['champ'];
            if ($this->normalizeString((string) $champ->getNom()) === $normalizedNom) {
                return $champData;
            }
        }

        return null;
    }

    /**
     * Cherche un champ par nom dans un tableau de Champ entities.
     *
     * @param Champ[] $enfants
     */
    private function matchChampByNomInArray(array $enfants, string $nom): ?Champ
    {
        $normalizedNom = $this->normalizeString($nom);

        foreach ($enfants as $enfant) {
            if ($this->normalizeString((string) $enfant->getNom()) === $normalizedNom) {
                return $enfant;
            }
        }

        return null;
    }

    /**
     * Normalise une chaîne en remplaçant les séquences d'espaces par un espace simple.
     */
    private function normalizeString(string $str): string
    {
        return (string) preg_replace('/\s+/', ' ', trim($str));
    }
}
