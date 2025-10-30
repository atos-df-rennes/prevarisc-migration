<?php

namespace App\Service\RessourcesSpecialisees\Etablissement;

use App\Interfaces\RessourcesSpecialiseesEtablissement;

class RessourcesSpecialiseesBupNamer implements RessourcesSpecialiseesEtablissement {
    public function generateName(array $formData): string {
        $name = 'bup_';

        $groupements = $formData['groupements'];
        $commune = $formData['commune'];

        $name .= $groupements.'_';
        $name .= $commune;

        return $name;
    }
}
