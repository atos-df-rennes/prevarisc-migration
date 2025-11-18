<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Namer;

use App\Interfaces\RessourcesSpecialiseesEtablissementNamer;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator;

/**
 * @phpstan-import-type FormData from RessourcesSpecialiseesEtablissementTextGenerator
 */
class RessourcesSpecialiseesIopNamer implements RessourcesSpecialiseesEtablissementNamer
{
    /**
     * @param FormData $formData
     */
    public function generateName(array $formData): string
    {
        return 'iop_0_0';
    }
}
