<?php

namespace App\Interfaces;

use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator;

/**
 * @phpstan-import-type FormData from RessourcesSpecialiseesEtablissementTextGenerator
 */
interface RessourcesSpecialiseesEtablissementTexter
{
    /**
     * @param FormData $formData
     */
    public function generateText(array $formData): string;
}
