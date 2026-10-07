<?php

namespace App\Twig;

use App\Service\DateDiffFormatterService;
use App\Service\Platau\PlatauTempsRestant;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

class SearchExtension
{
    public function __construct(private readonly PlatauTempsRestant $platauTempsRestant, private readonly DateDiffFormatterService $dateDiffFormatter)
    {
    }

    /**
     * Remplace toutes les occurrences de $search dans $subject de façon insensible à la casse.
     */
    #[AsTwigFilter(name: 'replace_ci')]
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
    #[AsTwigFunction(name: 'format_date_diff')]
    public function formatDateDiff(string $dateString): string
    {
        return $this->dateDiffFormatter->format($dateString);
    }

    /**
     * Retourne le temps restant pour une consultation Plat'AU (port de Service_Utils_TempsRestant::calculate).
     * Inclut le signe "-" pour les délais dépassés.
     */
    #[AsTwigFunction(name: 'platau_temps_restant')]
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
    #[AsTwigFunction(name: 'platau_couleur_temps_restant')]
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
