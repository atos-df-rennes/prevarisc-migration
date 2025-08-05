<?php

namespace App\Service;

class UtilsDate
{
    public function formatDateWithDayName(\DateTimeInterface $date): string
    {

        $formatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::FULL,      
            \IntlDateFormatter::NONE,     
            $date->getTimezone()->getName(),
            \IntlDateFormatter::GREGORIAN,
             "EEEE d MMM yyyy"            
        );

        return $formatter->format($date);
            
    }

    public function convertFromMySQL(\DateTimeInterface $date): string 
    {
        return $date->format('d/m/Y');
    }
}
