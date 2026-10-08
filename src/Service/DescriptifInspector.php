<?php

namespace App\Service;

use App\Constant\TypeChamp;
use App\Entity\Champ;
use App\Entity\Valeur;

/**
 * Inspecte le contenu des formulaires personnalisés (rubriques/champs/valeurs)
 * pour déterminer si des données ont été saisies.
 */
class DescriptifInspector
{
    /**
     * Indique si au moins un champ du descriptif contient une donnée saisie.
     *
     * @param list<array{rubrique: mixed, champs: list<array<string, mixed>>}> $rubriques
     */
    public function rubriquesHaveData(array $rubriques): bool
    {
        foreach ($rubriques as $rubrique) {
            foreach ($rubrique['champs'] as $champData) {
                /** @var Champ $champ */
                $champ = $champData['champ'];
                /** @var Valeur|null $valeur */
                $valeur = $champData['valeur'] ?? null;

                if ($this->champHasData($champ, $valeur, $champData)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Détermine si un champ contient une donnée saisie.
     * Pour les checkboxes, `false` est considéré comme absence de donnée (valeur par défaut).
     *
     * @param array<string, mixed> $champData
     */
    private function champHasData(Champ $champ, ?Valeur $valeur, array $champData): bool
    {
        $type = $champ->getType();
        $typeId = $type?->getId();

        if (TypeChamp::PARENT === $typeId) {
            if (array_key_exists('lignes', $champData) && [] !== $champData['lignes']) {
                return true;
            }

            if (array_key_exists('sous_champs', $champData)) {
                /** @var array<array<string, mixed>> $sousChamps */
                $sousChamps = $champData['sous_champs'];
                foreach ($sousChamps as $sousChamp) {
                    /** @var Champ $sousChampEntity */
                    $sousChampEntity = $sousChamp['champ'];
                    /** @var Valeur|null $sousValeur */
                    $sousValeur = $sousChamp['valeur'] ?? null;
                    if ($this->valeurHasData($sousValeur, $sousChampEntity)) {
                        return true;
                    }
                }
            }

            return false;
        }

        return $this->valeurHasData($valeur, $champ);
    }

    /**
     * Vérifie si une Valeur contient une donnée significative.
     * Pour les checkboxes, seul `true` est considéré comme une donnée saisie.
     */
    private function valeurHasData(?Valeur $valeur, Champ $champ): bool
    {
        if (null === $valeur) {
            return false;
        }

        $type = $champ->getType();
        if (null !== $type && TypeChamp::CHECKBOX === $type->getId()) {
            return true === $valeur->getCheckbox();
        }

        return true;
    }
}
