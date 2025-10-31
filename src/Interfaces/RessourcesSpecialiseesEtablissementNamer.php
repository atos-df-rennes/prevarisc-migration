<?php

namespace App\Interfaces;

use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator;

/**
 * @phpstan-import-type FormData from RessourcesSpecialiseesEtablissementTextGenerator
 */
interface RessourcesSpecialiseesEtablissementNamer
{
    /**
     * @param FormData $formData
     */
    public function generateName(array $formData): string;
}
