<?php

namespace App\Service\Formulaires;

use App\Constant\TypeChamp;
use App\Entity\TypeChamp as TypeChampEntity;
use App\Entity\Valeur;
use App\Utils\DateFormatterFr;

/**
 * Résout la représentation textuelle d'une valeur de champ de formulaire selon son type.
 *
 * Extrait de DossierRapportOdtService::resolveValeurForFormulaire() pour permettre
 * les tests unitaires sans infrastructure.
 */
final class ValeurResolver
{
    private function __construct()
    {
    }

    /**
     * Retourne la représentation textuelle d'une valeur selon le type du champ.
     *
     * @param TypeChampEntity|null $typeChamp Type Doctrine du champ (entité)
     * @param Valeur|null          $valeur    Valeur Doctrine
     */
    public static function resolve(?TypeChampEntity $typeChamp, ?Valeur $valeur): string
    {
        $typeId = null !== $typeChamp ? $typeChamp->getId() : null;

        if (TypeChamp::CHECKBOX === $typeId) {
            if (null === $valeur) {
                return 'Non';
            }

            $checkbox = $valeur->getCheckbox();

            return (true === $checkbox) ? 'Oui' : 'Non';
        }

        if (null === $valeur) {
            return '';
        }

        if (TypeChamp::DATE === $typeId) {
            $date = $valeur->getDate();
            if (null === $date) {
                return '';
            }

            return DateFormatterFr::formatLong($date);
        }

        if (TypeChamp::TEXTAREA === $typeId) {
            return $valeur->getLongStr() ?? '';
        }

        if (TypeChamp::NUMBER === $typeId) {
            $number = $valeur->getNumber();

            return null !== $number ? (string) $number : '';
        }

        return $valeur->getStr() ?? $valeur->getLongStr() ?? '';
    }
}
