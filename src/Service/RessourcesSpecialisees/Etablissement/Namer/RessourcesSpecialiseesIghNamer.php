<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Namer;

use App\Interfaces\RessourcesSpecialiseesEtablissementNamer;

/**
 * @phpstan-import-type FormData from \App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator
 */
class RessourcesSpecialiseesIghNamer implements RessourcesSpecialiseesEtablissementNamer {
    /**
     * @param FormData $formData
     */
    public function generateName(array $formData): string {
        $name = 'igh_';

        if (!array_key_exists('classes', $formData)) {
            $name .= '0_';
        } else {
            $classes = $formData['classes'];
            $name .= implode('-', $classes).'_';
        }

        $commissions = $formData['commissions'];
        $groupements = $formData['groupements'];
        $commune = $formData['commune'];

        $name .= $commissions.'_';
        $name .= $groupements.'_';
        $name .= $commune;

        return $name;
    }
}
