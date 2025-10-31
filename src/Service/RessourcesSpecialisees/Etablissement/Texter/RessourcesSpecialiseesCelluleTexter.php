<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Entity\EtablissementActivite;
use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;
use App\Repository\ClassementRepository;
use App\Repository\EtablissementActiviteRepository;
use App\ValueObject\EtablissementTexter;

class RessourcesSpecialiseesCelluleTexter implements RessourcesSpecialiseesEtablissementTexter {
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
        ;

        return 'Cellule ('.$this->etablissementTexter->getText().' - Toutes les catégories)';
    }
}
