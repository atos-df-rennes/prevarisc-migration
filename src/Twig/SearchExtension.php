<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SearchExtension extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('replace_ci', [$this, 'replaceCaseInsensitive']),
        ];
    }

    /**
     * Remplace toutes les occurrences de $search dans $subject de façon insensible à la casse.
     */
    public function replaceCaseInsensitive(string $subject, string $search, string $replace): string
    {
        if ('' === $search) {
            return $subject;
        }

        return str_ireplace($search, $replace, $subject);
    }
}
