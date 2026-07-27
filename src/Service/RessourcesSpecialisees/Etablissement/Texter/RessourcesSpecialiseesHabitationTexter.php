<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator;
use App\ValueObject\EtablissementTexter;

/**
 * @phpstan-import-type FormData from RessourcesSpecialiseesEtablissementTextGenerator
 */
class RessourcesSpecialiseesHabitationTexter implements RessourcesSpecialiseesEtablissementTexter
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
            ->setFamilles($formData['familles'] ?? null)
            ->setUseUserGroupements($formData['groupements'])
            ->setUseUserCommune($formData['commune'])
        ;

        return 'Habitation ('.$this->etablissementTexter->getText().')';
    }
}
