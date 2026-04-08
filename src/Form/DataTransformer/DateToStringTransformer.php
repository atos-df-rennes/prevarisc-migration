<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforme une date stockée en \DateTimeInterface en string (format d/m/Y) et vice-versa.
 *
 * Utilisé par ChampDateType pour les champs de type DATE dans les formulaires personnalisés.
 * Le format d/m/Y est celui attendu par le datepicker Bootstrap de l'application.
 */
class DateToStringTransformer implements DataTransformerInterface
{
    /**
     * Transforme un DateTime en string (d/m/Y) pour l'affichage dans le formulaire.
     *
     * @param \DateTimeInterface|null $value
     *
     * @return string
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        return $value->format('d/m/Y');
    }

    /**
     * Transforme une string (d/m/Y) en DateTime pour la persistance.
     *
     * @param string|null $value
     *
     * @return \DateTime|null
     */
    public function reverseTransform($value)
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $date = \DateTime::createFromFormat('d/m/Y', $value);

        return $date instanceof \DateTime ? $date : null;
    }
}
