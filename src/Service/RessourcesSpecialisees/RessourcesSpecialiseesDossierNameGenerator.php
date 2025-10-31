<?php

namespace App\Service\RessourcesSpecialisees;

use Symfony\Component\HttpFoundation\Request;

/**
 * @phpstan-import-type FormData from \App\Service\RessourcesSpecialisees\RessourcesSpecialiseesDossierTextGenerator
 */
class RessourcesSpecialiseesDossierNameGenerator
{
    public static function fromRequest(Request $request): string
    {
        /** @var FormData $formData */
        $formData = $request->request->get('ressource_specialisee_dossier');
        $name = 'dossier_';

        if (!array_key_exists('natures', $formData)) {
            return $name.'0';
        }

        $natures = $formData['natures'];

        return $name.implode('-', $natures);
    }
}
