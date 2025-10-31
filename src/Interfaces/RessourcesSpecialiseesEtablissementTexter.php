<?php

namespace App\Interfaces;

/**
 * @phpstan-import-type FormData from \App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator
 */
interface RessourcesSpecialiseesEtablissementTexter
{

    /**
     * @param FormData $formData
     */
    public function generateText(array $formData): string;
}
