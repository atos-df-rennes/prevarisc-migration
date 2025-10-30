<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;

// @todo: Utiliser un ValueObject
class RessourcesSpecialiseesBupTexter implements RessourcesSpecialiseesEtablissementTexter {
    public function generateText(array $formData): string {
        $text = 'BUP (';

        $groupements = filter_var($formData['groupements'], FILTER_VALIDATE_BOOLEAN);
        $commune = filter_var($formData['commune'], FILTER_VALIDATE_BOOLEAN);

        // @todo: Utiliser un tableau d'options implode par -
        $text .= $groupements ? "Sur les groupements de l'utilisateur" : 'Ignorer les groupements';
        $text .= ' - ';
        $text .= $commune ? "Sur la commune de l'utilisateur" : 'Ignorer la commune';

        return $text.')';
    }
}
