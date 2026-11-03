<?php

namespace App\Service\Descriptif;

use App\Constant\TypeChamp;
use App\Dto\Descriptif\ChampValueData;
use App\Dto\Descriptif\DescriptifFormData;
use App\Dto\Descriptif\RubriqueFormData;
use App\Dto\Descriptif\TableauRowData;
use App\Entity\Champ;
use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Entity\Rubrique;
use App\Entity\Valeur;
use App\Service\Descriptif;
use App\ValueObject\RubriqueEtat;

/**
 * Transforme la structure de données retournée par le service Descriptif en DTOs
 * prêts à alimenter les FormTypes Symfony.
 *
 * @see Descriptif::getForDossier()
 * @see Descriptif::getForEtablissement()
 *
 * @phpstan-import-type RubriqueData from Descriptif
 */
final class DescriptifFormHydrator
{
    /**
     * Hydrate un DescriptifFormData depuis les données d'un Dossier.
     *
     * @param list<RubriqueData> $rubriques
     */
    public function hydrateFromDossier(Dossier $dossier, array $rubriques): DescriptifFormData
    {
        return $this->hydrate($rubriques);
    }

    /**
     * Hydrate un DescriptifFormData depuis les données d'un Établissement.
     *
     * @param list<RubriqueData> $rubriques
     */
    public function hydrateFromEtablissement(Etablissement $etablissement, array $rubriques): DescriptifFormData
    {
        return $this->hydrate($rubriques);
    }

    /**
     * Construit le DescriptifFormData depuis la structure de rubriques.
     *
     * @param list<RubriqueData> $rubriques
     */
    private function hydrate(array $rubriques): DescriptifFormData
    {
        $rubriqueFormDatas = [];

        foreach ($rubriques as $item) {
            /** @var Rubrique $rubrique */
            $rubrique = $item['rubrique'];
            $rubriqueId = $rubrique->getId();
            if (null === $rubriqueId) {
                continue;
            }

            $champs = [];
            foreach ($item['champs'] as $champData) {
                /** @var Champ $champ */
                $champ = $champData['champ'];
                $champId = $champ->getId();
                if (null === $champId) {
                    continue;
                }

                $champs[$champId] = $this->hydrateChamp($champData);
            }

            $rubriqueFormDatas[$rubriqueId] = new RubriqueFormData(
                new RubriqueEtat($item['display'], $item['opened']),
                $champs
            );
        }

        return new DescriptifFormData($rubriqueFormDatas);
    }

    /**
     * Hydrate un ChampValueData depuis les données brutes d'un champ.
     *
     * @param array<string, mixed> $champData
     */
    private function hydrateChamp(array $champData): ChampValueData
    {
        /** @var Champ $champ */
        $champ = $champData['champ'];
        /** @var Valeur|null $valeur */
        $valeur = $champData['valeur'] ?? null;

        $type = $champ->getType();
        $typeId = $type?->getId();

        if (TypeChamp::PARENT === $typeId) {
            if ($champ->getTableau()) {
                /** @var list<array<int, Valeur>> $lignes */
                $lignes = $champData['lignes'] ?? [];

                return $this->hydrateTableau($champ, $lignes);
            }

            /** @var list<array{champ: Champ, valeur: Valeur|null}> $sousChamps */
            $sousChamps = $champData['sous_champs'] ?? [];

            return $this->hydrateGroupe($champ, $sousChamps);
        }

        $value = $this->extractValue($valeur, $typeId);
        $valeurId = $valeur?->getId();

        return new ChampValueData((int) $champ->getId(), $value, [], [], $valeurId);
    }

    /**
     * Extrait la valeur typée depuis une entité Valeur selon le TypeChamp.
     */
    private function extractValue(?Valeur $valeur, ?int $typeId): int|bool|\DateTimeInterface|string|null
    {
        if (!$valeur instanceof Valeur) {
            return null;
        }

        return match ($typeId) {
            TypeChamp::TEXTAREA => $valeur->getLongStr(),
            TypeChamp::NUMBER => $valeur->getNumber(),
            TypeChamp::CHECKBOX => $valeur->getCheckbox(),
            TypeChamp::DATE => $valeur->getDate(),
            default => $valeur->getStr(),
        };
    }

    /**
     * Hydrate un champ PARENT avec tableau=true.
     *
     * Chaque ligne est un array<champId, Valeur> (retourné par Descriptif::buildTableauLignes).
     *
     * @param list<array<int, Valeur>> $lignes
     */
    private function hydrateTableau(Champ $champ, array $lignes): ChampValueData
    {
        $rows = [];
        foreach ($lignes as $ligne) {
            $cells = [];
            $valeurIds = [];
            foreach ($ligne as $champId => $valeur) {
                $enfantChamp = $valeur->getChamp();
                $enfantTypeId = null;
                if (null !== $enfantChamp) {
                    $enfantType = $enfantChamp->getType();
                    $enfantTypeId = $enfantType?->getId();
                }

                $cells[$champId] = $this->extractValue($valeur, $enfantTypeId);
                $valeurIds[$champId] = $valeur->getId();
            }

            $rows[] = new TableauRowData($cells, $valeurIds);
        }

        return new ChampValueData((int) $champ->getId(), null, $rows);
    }

    /**
     * Hydrate un champ PARENT avec tableau=false (groupe de sous-champs).
     *
     * @param list<array{champ: Champ, valeur: Valeur|null}> $sousChamps
     */
    private function hydrateGroupe(Champ $champ, array $sousChamps): ChampValueData
    {
        $sousChampDatas = [];
        foreach ($sousChamps as $sc) {
            $enfant = $sc['champ'];
            $enfantId = $enfant->getId();
            if (null !== $enfantId) {
                $sousChampDatas[$enfantId] = $this->hydrateChamp($sc);
            }
        }

        return new ChampValueData((int) $champ->getId(), null, [], $sousChampDatas);
    }
}
