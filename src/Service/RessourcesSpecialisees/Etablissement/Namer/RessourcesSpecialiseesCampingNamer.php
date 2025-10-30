<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Namer;

use App\Interfaces\RessourcesSpecialiseesEtablissementNamer;

class RessourcesSpecialiseesCampingNamer implements RessourcesSpecialiseesEtablissementNamer {
    public function generateName(array $formData): string {
        return 'camp_0_0';
    }
}
