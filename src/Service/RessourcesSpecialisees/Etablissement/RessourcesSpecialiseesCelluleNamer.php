<?php

namespace App\Service\RessourcesSpecialisees\Etablissement;

use App\Interfaces\RessourcesSpecialiseesEtablissement;

class RessourcesSpecialiseesCelluleNamer implements RessourcesSpecialiseesEtablissement {
    public function generateName(array $formData): string {
        $name = 'cell_';

        $typesActivites = $formData['typesActivites'];

        $name .= implode('-', $typesActivites).'_0';

        return $name;
    }
}
