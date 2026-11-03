<?php

namespace App\Twig;

use App\Utils\Date;
use Twig\Attribute\AsTwigFilter;

/**
 * Extension pour ajouter un filtre personnalisé Twig pour formatter les dates en français avec le nom du jour.
 */
final class DateExtension
{
    /**
     * Formate une date en libellé français court avec le nom du jour (ex: "Lundi 12 Jan 2026").
     */
    #[AsTwigFilter(name: 'date_fr')]
    public function formatDateFr(?\DateTimeInterface $date): string
    {
        if (!$date instanceof \DateTimeInterface) {
            return '';
        }

        return Date::formatDateFr($date);
    }
}
