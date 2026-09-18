<?php

namespace App\Utils;

/**
 * Utilitaires de formatage de dates en français.
 *
 * Méthodes statiques réutilisables pour formater des dates au format long ou court.
 */
final class DateFormatterFr
{
    /**
     * Formate un objet DateTime en format long français : "d MMMM yyyy" (ex: "15 janvier 2024").
     */
    public static function formatLong(\DateTimeInterface $dt): string
    {
        $moisLongs = [
            'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
            'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
        ];

        return $dt->format('d').' '.$moisLongs[(int) $dt->format('n') - 1].' '.$dt->format('Y');
    }

    /**
     * Formate une date (string SQL ou null) en format court français : "d MMM yyyy" (ex: "15 janv. 2024").
     *
     * @param string|null $dateStr Date au format Y-m-d ou Y-m-d H:i:s
     */
    public static function formatShort(?string $dateStr): string
    {
        if (null === $dateStr || '' === $dateStr) {
            return '';
        }

        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $dateStr);
        if (!$dt instanceof \DateTime) {
            $dt = \DateTime::createFromFormat('Y-m-d', substr($dateStr, 0, 10));
        }

        if (!$dt instanceof \DateTime) {
            return '';
        }

        $moisCourts = [
            'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin',
            'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.',
        ];

        return $dt->format('j').' '.$moisCourts[(int) $dt->format('n') - 1].' '.$dt->format('Y');
    }

    /**
     * Formate une chaîne de date SQL en format long français.
     *
     * @param string|null $dateStr Date au format Y-m-d ou Y-m-d H:i:s
     */
    public static function formatLongFromStr(?string $dateStr): string
    {
        if (null === $dateStr || '' === $dateStr) {
            return '';
        }

        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $dateStr);
        if (!$dt instanceof \DateTime) {
            $dt = \DateTime::createFromFormat('Y-m-d', substr($dateStr, 0, 10));
        }

        if (!$dt instanceof \DateTime) {
            return '';
        }

        return self::formatLong($dt);
    }
}
