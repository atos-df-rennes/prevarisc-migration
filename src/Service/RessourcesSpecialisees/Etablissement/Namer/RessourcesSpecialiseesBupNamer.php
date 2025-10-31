<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Namer;

use App\Interfaces\RessourcesSpecialiseesEtablissementNamer;

/**
 * @phpstan-import-type FormData from \App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator
 */
class RessourcesSpecialiseesBupNamer implements RessourcesSpecialiseesEtablissementNamer {
    /**
     * @param FormData $formData
     */
    public function generateName(array $formData): string {
        $name = 'bup_';

        $groupements = $formData['groupements'];
        $commune = $formData['commune'];

        $name .= $groupements.'_';
        $name .= $commune;

        return $name;
    }
}
