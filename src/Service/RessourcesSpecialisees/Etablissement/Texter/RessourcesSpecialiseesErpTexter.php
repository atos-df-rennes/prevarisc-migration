<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Entity\Classement;
use App\Entity\EtablissementActivite;
use App\Entity\EtablissementCategorie;
use App\Entity\IghClasse;
use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;
use App\Repository\ClassementRepository;
use App\Repository\EtablissementActiviteRepository;
use App\Repository\EtablissementCategorieRepository;
use App\Repository\FamilleRepository;
use App\Repository\IghClasseRepository;

class RessourcesSpecialiseesErpTexter implements RessourcesSpecialiseesEtablissementTexter {
    /** @var \App\Repository\EtablissementActiviteRepository */
    private $etablissementActiviteRepository;

    /** @var \App\Repository\EtablissementCategorieRepository */
    private $categorieRepository;

    public function __construct(
        EtablissementActiviteRepository $etablissementActiviteRepository,
        EtablissementCategorieRepository $categorieRepository
    ) {
        $this->etablissementActiviteRepository = $etablissementActiviteRepository;
        $this->categorieRepository = $categorieRepository;
    }

    public function generateText(array $formData): string {
        $text = 'Établissement (';

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

        $text .= $types.' - ';

        $categories = 'Toutes les catégories';

        if (array_key_exists('categories', $formData) && is_array($formData['categories'])) {
            $categoriesIds = $formData['categories'];

            /** @var array<int, ?\App\Entity\EtablissementCategorie> $categoriesEntities */
            $categoriesEntities = array_map(function(string $categorieId) {
                return $this->categorieRepository->find($categorieId);
            }, $categoriesIds);
            /** @var array<int, EtablissementCategorie> $categoriesEntities */
            $categoriesEntities = array_filter($categoriesEntities);

            /** @var array<int, ?string> $categoriesLibelles */
            $categoriesLibelles = array_map(function(EtablissementCategorie $categorieId) {
                return $categorieId->getLibelle();
            }, $categoriesEntities);
            /** @var array<int, string> $categoriesLibelles */
            $categoriesLibelles = array_filter($categoriesLibelles);

            $categories = implode('-', $categoriesLibelles);
        }

        $text .= $categories.' - ';

        $commissions = filter_var($formData['commissions'], FILTER_VALIDATE_BOOLEAN);
        $groupements = filter_var($formData['groupements'], FILTER_VALIDATE_BOOLEAN);
        $commune = filter_var($formData['commune'], FILTER_VALIDATE_BOOLEAN);

        $text .= $commissions ? "Sur les commissions de l'utilisateur" : 'Ignorer les commissions';
        $text .= ' - ';
        $text .= $groupements ? "Sur les groupements de l'utilisateur" : 'Ignorer les groupements';
        $text .= ' - ';
        $text .= $commune ? "Sur la commune de l'utilisateur" : 'Ignorer la commune';

        return $text;
    }
}
