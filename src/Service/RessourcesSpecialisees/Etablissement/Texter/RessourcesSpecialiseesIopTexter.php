<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;

class RessourcesSpecialiseesIopTexter implements RessourcesSpecialiseesEtablissementTexter {
    public function generateText(array $formData): string {
        return 'IOP (Ignorer les groupements - Ignorer la commune)';
    }
}
