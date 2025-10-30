<?php

namespace App\Service\RessourcesSpecialisees\Etablissement;

use App\Interfaces\RessourcesSpecialiseesEtablissement;

// @todo: Utiliser un DTO plutôt que le tableau
class RessourcesSpecialiseesHabitationNamer implements RessourcesSpecialiseesEtablissement {
    public function generateName(array $formData): string {
        $name = 'hab_';

        $familles = $formData['familles'];
        $groupements = $formData['groupements'];
        $commune = $formData['commune'];

        $name .= implode('-', $familles).'_';
        $name .= $groupements.'_';
        $name .= $commune;

        return $name;
    }
}
