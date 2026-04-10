<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

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
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('format_date_diff', [$this, 'formatDateDiff']),
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

    /**
     * Retourne le temps écoulé depuis une date (port de formatDateDiff du legacy).
     * Utilisé pour l'affichage "Réponse à faire (X jours)" sur les dossiers de type courrier.
     */
    public function formatDateDiff(string $dateString): string
    {
        try {
            $date = new \DateTime(substr($dateString, 0, 10));
            $now = new \DateTime();
            $diff = $now->diff($date);

            $parts = [];
            if ($diff->y > 0) {
                $parts[] = $diff->y.($diff->y > 1 ? ' ans' : ' an');
            }

            if ($diff->m > 0) {
                $parts[] = $diff->m.' mois';
            }

            if ($diff->d > 0) {
                $parts[] = $diff->d.($diff->d > 1 ? ' jours' : ' jour');
            }

            if ([] === $parts) {
                return "aujourd'hui";
            }

            return implode(', ', $parts);
        } catch (\Exception $exception) {
            return '';
        }
    }
}
