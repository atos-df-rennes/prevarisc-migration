<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Namer;

use App\Interfaces\RessourcesSpecialiseesEtablissementNamer;

class RessourcesSpecialiseesManifestationTemporaireNamer implements RessourcesSpecialiseesEtablissementNamer {
    public function generateName(array $formData): string {
        return 'temp_0_0';
    }
}
