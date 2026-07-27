<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator;
use App\ValueObject\EtablissementTexter;

/**
 * @phpstan-import-type FormData from RessourcesSpecialiseesEtablissementTextGenerator
 */
class RessourcesSpecialiseesIghTexter implements RessourcesSpecialiseesEtablissementTexter
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
            ->setClasses($formData['classes'] ?? null)
            ->setUseUserCommissions($formData['commissions'])
            ->setUseUserGroupements($formData['groupements'])
            ->setUseUserCommune($formData['commune'])
        ;

        return 'IGH ('.$this->etablissementTexter->getText().')';
    }
}
