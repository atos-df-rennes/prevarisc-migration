<?php

namespace App\Service\RessourcesSpecialisees;

use Symfony\Component\HttpFoundation\Request;

class RessourcesSpecialiseesDossierNameGenerator
{
    public static function fromRequest(Request $request): string
    {
        $formData = $request->request->get('ressource_specialisee_dossier');
        $natures = $formData['natures'];

        $name = 'dossier_';

        if (null === $natures) {
            return $name.'0';
        }

        return $name.implode('-', $natures);
    }
}
