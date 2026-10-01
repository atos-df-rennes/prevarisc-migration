<?php

namespace App\Service\RessourcesSpecialisees;

use App\Entity\DossierNatureListe;
use App\Repository\DossierNatureListeRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * @phpstan-type FormData array{
 *     text: string,
 *     natures?: array<int, string>
 * }
 */
class RessourcesSpecialiseesDossierTextGenerator
{
    public function __construct(private readonly DossierNatureListeRepository $naturesRepository)
    {
    }

    public function fromRequest(Request $request): string
    {
        /** @var FormData $formData */
        $formData = $request->request->all('ressource_specialisee_dossier');
        $formText = $formData['text'];

        if ('' !== $formText) {
            return $formText;
        }

        $text = 'Dossier';

        $natures = 'Toutes les natures';

        if (array_key_exists('natures', $formData)) {
            $naturesIds = $formData['natures'];

            /** @var array<int, ?DossierNatureListe> $naturesEntities */
            $naturesEntities = array_map(fn (string $idNature) => $this->naturesRepository->find($idNature), $naturesIds);
            /** @var array<int, DossierNatureListe> $naturesEntities */
            $naturesEntities = array_filter($naturesEntities);

            /** @var array<int, ?string> $naturesLibelles */
            $naturesLibelles = array_map(fn (DossierNatureListe $nature): ?string => $nature->getLibelle(), $naturesEntities);
            /** @var array<int, string> $naturesLibelles */
            $naturesLibelles = array_filter($naturesLibelles);

            $natures = implode(' - ', $naturesLibelles);
        }

        return $text.' ('.$natures.')';
    }
}
