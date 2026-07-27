<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator;
use App\ValueObject\EtablissementTexter;

/**
 * @phpstan-import-type FormData from RessourcesSpecialiseesEtablissementTextGenerator
 */
class RessourcesSpecialiseesCelluleTexter implements RessourcesSpecialiseesEtablissementTexter
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
            ->setTypesActivites($formData['typesActivites'] ?? null)
        ;

        return 'Cellule ('.$this->etablissementTexter->getText().' - Toutes les catégories)';
    }
}
