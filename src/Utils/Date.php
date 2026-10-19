<?php

namespace App\Utils;

final class Date
{
    /**
     * Formate une date en libellé français court (ex: "Lundi 12 Jan 2026").
     * Port de Zend_Date::get(WEEKDAY DAY_SHORT MONTH_NAME_SHORT YEAR, 'fr').
     */
    public static function formatDateFr(\DateTimeInterface $date): string
    {
        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $months = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

        return $days[(int) $date->format('w')].' '.$date->format('d').' '.$months[(int) $date->format('n')].' '.$date->format('Y');
    }
}
