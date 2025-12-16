<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforme une date stockée en string (format Y-m-d) en DateTime et vice-versa.
 *
 * Dans le legacy Prevarisc, le champ dateCommission est stocké en VARCHAR(10)
 * avec le format Y-m-d (ex: "2025-12-16"). Ce transformer permet d'utiliser
 * un DateType dans le formulaire tout en conservant la compatibilité avec la base.
 */
class DateCommissionTransformer implements DataTransformerInterface
{
    /**
     * Transforme une string (Y-m-d) en DateTime pour l'affichage dans le form.
     *
     * @param string|null $value Valeur en base (ex: "2025-12-16")
     */
    public function transform($value): ?\DateTime
    {
        if (null === $value || '' === $value) {
            return null;
        }

        // Conversion Y-m-d → DateTime
        $date = \DateTime::createFromFormat('Y-m-d', $value);

        if (false === $date) {
            // En cas d'erreur, on retourne null plutôt que de planter
            return null;
        }

        return $date;
    }

    /**
     * Transforme un DateTime en string (Y-m-d) pour la sauvegarde en base.
     *
     * @param \DateTime|null $value Valeur du formulaire
     */
    public function reverseTransform($value): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof \DateTimeInterface) {
            throw new TransformationFailedException('Expected a \DateTimeInterface.');
        }

        // Conversion DateTime → Y-m-d
        return $value->format('Y-m-d');
    }
}
