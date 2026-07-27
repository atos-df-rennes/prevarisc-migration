<?php

namespace App\Twig;

use App\Service\DateDiffFormatterService;
use App\Service\PlatauTempsRestant;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class SearchExtension extends AbstractExtension
{
    public function __construct(private PlatauTempsRestant $platauTempsRestant, private DateDiffFormatterService $dateDiffFormatter)
    {
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
     * Délègue à DateDiffFormatterService.
     */
    public function formatDateDiff(string $dateString): string
    {
        return $this->dateDiffFormatter->format($dateString);
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
        } catch (\Exception) {
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
        } catch (\Exception) {
            return 'default';
        }
    }
}
