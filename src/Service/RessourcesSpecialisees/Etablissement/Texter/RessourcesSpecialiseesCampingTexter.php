<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;

/**
 * @phpstan-import-type FormData from \App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator
 */
class RessourcesSpecialiseesCampingTexter implements RessourcesSpecialiseesEtablissementTexter {
    /**
     * @param FormData $formData
     */
    public function generateText(array $formData): string {
        return 'Camping (Ignorer les groupements - Ignorer la commune)';
    }
}
