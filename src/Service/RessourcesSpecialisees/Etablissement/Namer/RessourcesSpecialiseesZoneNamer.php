<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Namer;

use App\Interfaces\RessourcesSpecialiseesEtablissementNamer;

class RessourcesSpecialiseesZoneNamer implements RessourcesSpecialiseesEtablissementNamer {
    public function generateName(array $formData): string {
        $name = 'zone_';

        if (!array_key_exists('classements', $formData)) {
            return $name.'0_0_0';
        }

        $classements = $formData['classements'];

        return $name.implode('-', $classements).'_0_0';
    }
}
