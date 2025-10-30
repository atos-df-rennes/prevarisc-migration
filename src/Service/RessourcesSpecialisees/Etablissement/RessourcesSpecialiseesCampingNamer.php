<?php

namespace App\Service\RessourcesSpecialisees\Etablissement;

use App\Interfaces\RessourcesSpecialiseesEtablissement;

class RessourcesSpecialiseesCampingNamer implements RessourcesSpecialiseesEtablissement {
    public function generateName(array $formData): string {
        return 'camp_0_0';
    }
}
