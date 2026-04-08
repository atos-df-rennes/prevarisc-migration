<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforme une date stockée en \DateTimeInterface en string (format Y-m-d) et vice-versa.
 *
 * Utilisé par ChampDateType pour les champs de type DATE dans les formulaires personnalisés.
 * Le format Y-m-d est celui attendu par input[type="date"] (HTML5).
 */
class DateToStringTransformer implements DataTransformerInterface
{
    /**
     * Transforme un DateTime en string (Y-m-d) pour l'affichage dans le formulaire.
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

        return $value->format('Y-m-d');
    }

    /**
     * Transforme une string (Y-m-d) en DateTime pour la persistance.
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

        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date instanceof \DateTime ? $date : null;
    }
}
