<?php

namespace App\Twig;

use App\Service\PlatauTempsRestant;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class SearchExtension extends AbstractExtension
{
    /** @var PlatauTempsRestant */
    private $platauTempsRestant;

    public function __construct(PlatauTempsRestant $platauTempsRestant)
    {
        $this->platauTempsRestant = $platauTempsRestant;
    }

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
            new TwigFunction('platau_temps_restant', [$this, 'platauTempsRestant']),
            new TwigFunction('platau_couleur_temps_restant', [$this, 'platauCouleurTempsRestant']),
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

    /**
     * Retourne le temps restant pour une consultation Plat'AU (port de Service_Utils_TempsRestant::calculate).
     * Inclut le signe "-" pour les délais dépassés.
     */
    public function platauTempsRestant(string $dateString): string
    {
        try {
            $date = new \DateTime(substr($dateString, 0, 10));

            return $this->platauTempsRestant->calculate($date);
        } catch (\Exception $exception) {
            return '';
        }
    }

    /**
     * Retourne la couleur Bootstrap du label Plat'AU en fonction du temps restant.
     * Valeurs possibles : success, warning, danger, default.
     */
    public function platauCouleurTempsRestant(string $dateString): string
    {
        try {
            $date = new \DateTime(substr($dateString, 0, 10));

            return $this->platauTempsRestant->getCouleurTempsRestant($date);
        } catch (\Exception $exception) {
            return 'default';
        }
    }
}
