<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Namer;

use App\Interfaces\RessourcesSpecialiseesEtablissementNamer;

class RessourcesSpecialiseesBupNamer implements RessourcesSpecialiseesEtablissementNamer {
    public function generateName(array $formData): string {
        $name = 'bup_';

        $groupements = $formData['groupements'];
        $commune = $formData['commune'];

        $name .= $groupements.'_';
        $name .= $commune;

        return $name;
    }
}
