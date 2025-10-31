<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Namer;

use App\Interfaces\RessourcesSpecialiseesEtablissementNamer;

/**
 * @phpstan-import-type FormData from \App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator
 */
class RessourcesSpecialiseesCampingNamer implements RessourcesSpecialiseesEtablissementNamer {
    /**
     * @param FormData $formData
     */
    public function generateName(array $formData): string {
        return 'camp_0_0';
    }
}
