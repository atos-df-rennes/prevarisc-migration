<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Entity\Classement;
use App\Entity\Famille;
use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;
use App\Repository\ClassementRepository;
use App\Repository\FamilleRepository;
use App\ValueObject\EtablissementTexter;

class RessourcesSpecialiseesHabitationTexter implements RessourcesSpecialiseesEtablissementTexter {
    /** @var EtablissementTexter */
    private $etablissementTexter;

    public function __construct(
        EtablissementTexter $etablissementTexter
    ) {
        $this->etablissementTexter = $etablissementTexter;
    }

    public function generateText(array $formData): string {
        $this->etablissementTexter
            ->setFamilles($formData['familles'])
            ->setUseUserGroupements($formData['groupements'])
            ->setUseUserCommune($formData['commune'])
        ;

        return 'Habitation ('.$this->etablissementTexter->getText().')';
    }
}
