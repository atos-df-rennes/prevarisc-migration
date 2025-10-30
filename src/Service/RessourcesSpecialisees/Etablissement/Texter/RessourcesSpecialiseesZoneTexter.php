<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Entity\Classement;
use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;
use App\Repository\ClassementRepository;

class RessourcesSpecialiseesZoneTexter implements RessourcesSpecialiseesEtablissementTexter {
    /** @var ClassementRepository */
    private $classementRepository;

    public function __construct(ClassementRepository $classementRepository) {
        $this->classementRepository = $classementRepository;
    }

    public function generateText(array $formData): string {
        $text = 'Zone (';

        $classements = 'Tous les classements';

        if (array_key_exists('classements', $formData) && is_array($formData['classements'])) {
            $classementsIds = $formData['classements'];

            /** @var array<int, ?Classement> $classementsEntities */
            $classementsEntities = array_map(function(string $classementId) {
                return $this->classementRepository->find($classementId);
            }, $classementsIds);
            /** @var array<int, Classement> $classementsEntities */
            $classementsEntities = array_filter($classementsEntities);

            /** @var array<int, ?string> $classementsLibelles */
            $classementsLibelles = array_map(function(Classement $classementId) {
                return $classementId->getLibelle();
            }, $classementsEntities);
            /** @var array<int, string> $classementsLibelles */
            $classementsLibelles = array_filter($classementsLibelles);

            $classements = implode('-', $classementsLibelles);
        }

        return $text.$classements.' - Ignorer les groupements - Ignorer la commune)';
    }
}
