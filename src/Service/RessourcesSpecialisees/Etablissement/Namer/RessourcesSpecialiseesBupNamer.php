<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Namer;

use App\Interfaces\RessourcesSpecialiseesEtablissementNamer;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator;

/**
 * @phpstan-import-type FormData from RessourcesSpecialiseesEtablissementTextGenerator
 */
final class RessourcesSpecialiseesBupNamer implements RessourcesSpecialiseesEtablissementNamer
{
    /**
     * @param FormData $formData
     */
    public function generateName(array $formData): string
    {
        $name = 'bup_';

        $groupements = $formData['groupements'];
        $commune = $formData['commune'];

        $name .= $groupements.'_';

        return $name.$commune;
    }
}
