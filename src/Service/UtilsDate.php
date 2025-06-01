<?php

namespace App\Service;

class UtilsDate
{
    public function formatDateWithDayName(\DateTimeInterface $date): string
    {
        if(!$date){  }
            $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
            $jour = $jours[(int)$date->format('w')];
            return $jour . ' ' . $date->format('d/m/Y');
            
    }

    public function convertFromMySQL(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
