<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Entity\EtablissementActivite;
use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;
use App\Repository\ClassementRepository;
use App\Repository\EtablissementActiviteRepository;

class RessourcesSpecialiseesCelluleTexter implements RessourcesSpecialiseesEtablissementTexter {
    /** @var \App\Repository\EtablissementActiviteRepository */
    private $etablissementActiviteRepository;

    public function __construct(EtablissementActiviteRepository $etablissementActiviteRepository) {
        $this->etablissementActiviteRepository = $etablissementActiviteRepository;
    }

    public function generateText(array $formData): string {
        $text = 'Cellule (';

        $types = 'Tous les types';

        if (array_key_exists('typesActivites', $formData) && is_array($formData['typesActivites'])) {
            $typesIds = $formData['typesActivites'];

            /** @var array<int, ?EtablissementActivite> $typesEntities */
            $typesEntities = array_map(function(string $typeId) {
                return $this->etablissementActiviteRepository->find($typeId);
            }, $typesIds);
            /** @var array<int, EtablissementActivite> $typesEntities */
            $typesEntities = array_filter($typesEntities);

            /** @var array<int, ?string> $typesLibelles */
            $typesLibelles = array_map(function(EtablissementActivite $typeId) {
                return $typeId->getLibelle();
            }, $typesEntities);
            /** @var array<int, string> $typesLibelles */
            $typesLibelles = array_filter($typesLibelles);

            $types = implode('-', $typesLibelles);
        }

        return $text.$types.' - Toutes les catégories)';
    }
}
