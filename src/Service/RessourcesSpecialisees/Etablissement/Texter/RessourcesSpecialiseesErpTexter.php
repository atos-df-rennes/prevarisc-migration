<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;
use App\ValueObject\EtablissementTexter;

class RessourcesSpecialiseesErpTexter implements RessourcesSpecialiseesEtablissementTexter {
    /** @var EtablissementTexter */
    private $etablissementTexter;

    public function __construct(
        EtablissementTexter $etablissementTexter
    ) {
        $this->etablissementTexter = $etablissementTexter;
    }

    public function generateText(array $formData): string {
        $this->etablissementTexter
            ->setTypesActivites($formData['typesActivites'])
            ->setCategories($formData['categories'])
            ->setUseUserCommissions($formData['commissions'])
            ->setUseUserGroupements($formData['groupements'])
            ->setUseUserCommune($formData['commune'])
        ;

        return 'Établissement ('.$this->etablissementTexter->getText().')';
    }
}
