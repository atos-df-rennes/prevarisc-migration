<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforme une valeur booléenne en string ('1' ou '0') et vice-versa.
 *
 * Utilisé pour les champs ChoiceType dont les valeurs sont des strings '1'/'0'
 * mais dont la donnée métier est un bool. Le transformer s'insère en tant que
 * model transformer : le côté "model" est un bool, le côté "normalized" est
 * une string que ChoiceType peut exploiter directement.
 *
 * Exemple d'usage : champ 'prescription' dans TexteApplicableType.
 */
class BooleanStringTransformer implements DataTransformerInterface
{
    /**
     * Transforme un bool (donnée modèle) en string pour l'affichage dans le form.
     *
     * @param bool|null $value Valeur booléenne (true/false) ou null
     *
     * @return string|null '1' si true, '0' si false, null si null
     */
    public function transform($value): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value ? '1' : '0';
    }

    /**
     * Transforme une string soumise par le formulaire en bool (donnée modèle).
     *
     * @param string|null $value Valeur soumise ('1', '0', 'true', 'false', etc.) ou null
     *
     * @return bool true ou false selon la valeur soumise
     */
    public function reverseTransform($value): bool
    {
        if (null === $value || '' === $value) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
