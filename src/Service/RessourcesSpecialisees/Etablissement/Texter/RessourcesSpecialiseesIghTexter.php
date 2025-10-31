<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Entity\Classement;
use App\Entity\IghClasse;
use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;
use App\Repository\ClassementRepository;
use App\Repository\FamilleRepository;
use App\Repository\IghClasseRepository;
use App\ValueObject\EtablissementTexter;

class RessourcesSpecialiseesIghTexter implements RessourcesSpecialiseesEtablissementTexter {
    /** @var EtablissementTexter */
    private $etablissementTexter;

    public function __construct(
        EtablissementTexter $etablissementTexter
    ) {
        $this->etablissementTexter = $etablissementTexter;
    }

    public function generateText(array $formData): string {
        $this->etablissementTexter
            ->setClasses($formData['classes'])
            ->setUseUserCommissions($formData['commissions'])
            ->setUseUserGroupements($formData['groupements'])
            ->setUseUserCommune($formData['commune'])
        ;

        return 'IGH ('.$this->etablissementTexter->getText().')';
    }
}
