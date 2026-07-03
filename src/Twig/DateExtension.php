<?php

namespace App\Twig;

use App\Utils\Date;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Extension pour ajouter un filtre personnalisé Twig pour formatter les dates en français avec le nom du jour.
 */
class DateExtension extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('date_fr', [$this, 'formatDateFr']),
        ];
    }

    /**
     * Formate une date en libellé français court avec le nom du jour (ex: "Lundi 12 Jan 2026").
     */
    public function formatDateFr(?\DateTimeInterface $date): string
    {
        if (null === $date) {
            return '';
        }

        return Date::formatDateFr($date);
    }
}
