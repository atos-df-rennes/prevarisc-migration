<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Namer;

use App\Interfaces\RessourcesSpecialiseesEtablissementNamer;

/**
 * @phpstan-import-type FormData from \App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator
 */
class RessourcesSpecialiseesZoneNamer implements RessourcesSpecialiseesEtablissementNamer {
    /**
     * @param FormData $formData
     */
    public function generateName(array $formData): string {
        $name = 'zone_';

        if (!array_key_exists('classements', $formData)) {
            return $name.'0_0_0';
        }

        $classements = $formData['classements'];

        return $name.implode('-', $classements).'_0_0';
    }
}
