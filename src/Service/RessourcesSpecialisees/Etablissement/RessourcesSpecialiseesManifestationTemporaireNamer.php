<?php

namespace App\Service\RessourcesSpecialisees\Etablissement;

use App\Interfaces\RessourcesSpecialiseesEtablissement;

class RessourcesSpecialiseesManifestationTemporaireNamer implements RessourcesSpecialiseesEtablissement {
    public function generateName(array $formData): string {
        return 'temp_0_0';
    }
}
