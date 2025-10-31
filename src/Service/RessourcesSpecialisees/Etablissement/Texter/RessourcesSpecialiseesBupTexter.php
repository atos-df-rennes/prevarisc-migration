<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;
use App\ValueObject\EtablissementTexter;

class RessourcesSpecialiseesBupTexter implements RessourcesSpecialiseesEtablissementTexter {
    /** @var EtablissementTexter */
    private $etablissementTexter;

    public function __construct(
        EtablissementTexter $etablissementTexter
    ) {
        $this->etablissementTexter = $etablissementTexter;
    }

    public function generateText(array $formData): string {
        $this->etablissementTexter
            ->setUseUserGroupements($formData['groupements'])
            ->setUseUserCommune($formData['commune'])
        ;

        return 'BUP ('.$this->etablissementTexter->getText().')';
    }
}
