<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Namer;

use App\Interfaces\RessourcesSpecialiseesEtablissementNamer;

class RessourcesSpecialiseesErpNamer implements RessourcesSpecialiseesEtablissementNamer {
    public function generateName(array $formData): string {
        $name = 'erp_';

        if (!array_key_exists('typesActivites', $formData)) {
            $name .= '0_';
        } else {
            $typesActivites = $formData['typesActivites'];
            $name .= implode('-', $typesActivites).'_';
        }

        if (!array_key_exists('categories', $formData)) {
            $name .= '0_';
        } else {
            $categories = $formData['categories'];
            $name .= implode('-', $categories).'_';
        }

        $commissions = $formData['commissions'];
        $groupements = $formData['groupements'];
        $commune = $formData['commune'];

        $name .= $commissions.'_';
        $name .= $groupements.'_';
        $name .= $commune;

        return $name;
    }
}
