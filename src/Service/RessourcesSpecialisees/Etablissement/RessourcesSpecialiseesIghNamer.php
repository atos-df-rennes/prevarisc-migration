<?php

namespace App\Service\RessourcesSpecialisees\Etablissement;

use App\Interfaces\RessourcesSpecialiseesEtablissement;

class RessourcesSpecialiseesIghNamer implements RessourcesSpecialiseesEtablissement {
    public function generateName(array $formData): string {
        $name = 'igh_';

        $classes = $formData['classes'];
        $commissions = $formData['commissions'];
        $groupements = $formData['groupements'];
        $commune = $formData['commune'];

        $name .= implode('-', $classes).'_';
        $name .= $commissions.'_';
        $name .= $groupements.'_';
        $name .= $commune;

        return $name;
    }
}
