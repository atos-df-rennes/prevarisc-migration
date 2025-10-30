<?php

namespace App\Service\RessourcesSpecialisees\Etablissement;

use App\Interfaces\RessourcesSpecialiseesEtablissement;

class RessourcesSpecialiseesZoneNamer implements RessourcesSpecialiseesEtablissement {
    public function generateName(array $formData): string {
        $name = 'hzone_';

        $classements = $formData['classements'];

        $name .= implode('-', $classements).'_0_0';

        return $name;
    }
}
