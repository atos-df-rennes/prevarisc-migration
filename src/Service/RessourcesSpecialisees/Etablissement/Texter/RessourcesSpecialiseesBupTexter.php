<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator;
use App\ValueObject\EtablissementTexter;

/**
 * @phpstan-import-type FormData from RessourcesSpecialiseesEtablissementTextGenerator
 */
class RessourcesSpecialiseesBupTexter implements RessourcesSpecialiseesEtablissementTexter
{
    public function __construct(private readonly EtablissementTexter $etablissementTexter)
    {
    }

    /**
     * @param FormData $formData
     */
    public function generateText(array $formData): string
    {
        $this->etablissementTexter
            ->setUseUserGroupements($formData['groupements'])
            ->setUseUserCommune($formData['commune'])
        ;

        return 'BUP ('.$this->etablissementTexter->getText().')';
    }
}
