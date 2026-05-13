<?php

namespace App\Service;

/**
 * Service dédié au formatage de la différence entre une date et maintenant.
 *
 * Extrait de SearchExtension pour éviter les dépendances croisées entre
 * Twig Extensions.
 */
class DateDiffFormatterService
{
    /**
     * Retourne le temps écoulé depuis une date donnée (port de formatDateDiff du legacy).
     *
     * Utilisé notamment pour l'affichage "Réponse à faire (X jours)" sur les
     * dossiers de type courrier.
     *
     * @param string $dateString Date au format YYYY-MM-DD ou YYYY-MM-DD HH:MM:SS
     *
     * @return string Chaîne lisible (ex : "2 ans, 3 mois"), "aujourd'hui" ou "" en cas d'erreur
     */
    public function format(string $dateString): string
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
