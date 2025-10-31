<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;

/**
 * @phpstan-import-type FormData from \App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator
 */
class RessourcesSpecialiseesManifestationTemporaireTexter implements RessourcesSpecialiseesEtablissementTexter {

    /**
     * @param FormData $formData
     */
    public function generateText(array $formData): string {
        return 'Manifestation temporaire (Ignorer les groupements - Ignorer la commune)';
    }
}
