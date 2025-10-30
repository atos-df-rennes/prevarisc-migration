<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Namer;

use App\Interfaces\RessourcesSpecialiseesEtablissementNamer;

class RessourcesSpecialiseesIopNamer implements RessourcesSpecialiseesEtablissementNamer {
    public function generateName(array $formData): string {
        return 'iop_0_0';
    }
}
