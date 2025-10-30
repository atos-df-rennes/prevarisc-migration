<?php

namespace App\Service\RessourcesSpecialisees;

use App\Entity\DossierNatureListe;
use App\Repository\DossierNatureListeRepository;
use Symfony\Component\HttpFoundation\Request;

class RessourcesSpecialiseesDossierTextGenerator
{
    /** @var DossierNatureListeRepository */
    private $naturesRepository;

    public function __construct(DossierNatureListeRepository $naturesRepository)
    {
        $this->naturesRepository = $naturesRepository;
    }

    public function fromRequest(Request $request): string
    {
        $formData = $request->request->get('ressource_specialisee_dossier');
        $formText = $formData['text'];

        if ('' !== $formText) {
            return $formText;
        }

        $natures = $formData['natures'];
        $text = 'Dossier';

        if (null === $natures) {
            return $text.' (Toutes les natures)';
        }

        /** @var array<int, ?DossierNatureListe> $naturesEntities */
        $naturesEntities = array_map(function (string $idNature) {
            return $this->naturesRepository->find($idNature);
        }, $natures);
        /** @var array<int, DossierNatureListe> $naturesEntities */
        $naturesEntities = array_filter($naturesEntities);

        /** @var array<int, ?string> $naturesLibelles */
        $naturesLibelles = array_map(function (DossierNatureListe $nature) {
            return $nature->getLibelle();
        }, $naturesEntities);
        /** @var array<int, string> $naturesLibelles */
        $naturesLibelles = array_filter($naturesLibelles);

        return $text.' ('.implode(' - ', $naturesLibelles).')';
    }
}
