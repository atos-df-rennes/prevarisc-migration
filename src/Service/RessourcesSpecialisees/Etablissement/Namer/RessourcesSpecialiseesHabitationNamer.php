<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Namer;

use App\Interfaces\RessourcesSpecialiseesEtablissementNamer;

/**
 * @phpstan-import-type FormData from \App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator
 */
class RessourcesSpecialiseesHabitationNamer implements RessourcesSpecialiseesEtablissementNamer {
    /**
     * @param FormData $formData
     */
    public function generateName(array $formData): string {
        $name = 'hab_';

        if (!array_key_exists('familles', $formData)) {
            $name .= '0_';
        } else {
            $familles = $formData['familles'];
            $name .= implode('-', $familles).'_';
        }

        $groupements = $formData['groupements'];
        $commune = $formData['commune'];

        $name .= $groupements.'_';
        $name .= $commune;

        return $name;
    }
}
