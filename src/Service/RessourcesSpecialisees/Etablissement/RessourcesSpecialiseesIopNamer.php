<?php

namespace App\Service\RessourcesSpecialisees\Etablissement;

use App\Interfaces\RessourcesSpecialiseesEtablissement;

class RessourcesSpecialiseesIopNamer implements RessourcesSpecialiseesEtablissement {
    public function generateName(array $formData): string {
        return 'iop_0_0';
    }
}
