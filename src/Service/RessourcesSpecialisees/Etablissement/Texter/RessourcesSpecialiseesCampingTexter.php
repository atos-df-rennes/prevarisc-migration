<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;

class RessourcesSpecialiseesCampingTexter implements RessourcesSpecialiseesEtablissementTexter {
    public function generateText(array $formData): string {
        return 'Camping (Ignorer les groupements - Ignorer la commune)';
    }
}
