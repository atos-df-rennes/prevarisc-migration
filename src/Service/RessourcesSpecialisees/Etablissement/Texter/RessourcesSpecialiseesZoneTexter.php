<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator;
use App\ValueObject\EtablissementTexter;

/**
 * @phpstan-import-type FormData from RessourcesSpecialiseesEtablissementTextGenerator
 */
class RessourcesSpecialiseesZoneTexter implements RessourcesSpecialiseesEtablissementTexter
{
    public function __construct(private EtablissementTexter $etablissementTexter)
    {
    }

    /**
     * @param FormData $formData
     */
    public function generateText(array $formData): string
    {
        $this->etablissementTexter
            ->setClassements($formData['classements'] ?? null)
        ;

        return 'Zone ('.$this->etablissementTexter->getText().' - Ignorer les groupements - Ignorer la commune)';
    }
}
