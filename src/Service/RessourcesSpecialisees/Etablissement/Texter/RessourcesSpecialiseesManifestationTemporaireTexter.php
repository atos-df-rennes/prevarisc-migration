<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;

class RessourcesSpecialiseesManifestationTemporaireTexter implements RessourcesSpecialiseesEtablissementTexter {
    public function generateText(array $formData): string {
        return 'Manifestation temporaire (Ignorer les groupements - Ignorer la commune)';
    }
}
