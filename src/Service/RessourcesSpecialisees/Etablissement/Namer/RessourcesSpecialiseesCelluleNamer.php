<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Namer;

use App\Interfaces\RessourcesSpecialiseesEtablissementNamer;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator;

/**
 * @phpstan-import-type FormData from RessourcesSpecialiseesEtablissementTextGenerator
 */
class RessourcesSpecialiseesCelluleNamer implements RessourcesSpecialiseesEtablissementNamer
{
    /**
     * @param FormData $formData
     */
    public function generateName(array $formData): string
    {
        $name = 'cell_';

        if (!array_key_exists('typesActivites', $formData)) {
            return $name.'0_0';
        }

        $typesActivites = $formData['typesActivites'];

        return $name.implode('-', $typesActivites).'_0';
    }
}
