<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Entity\Classement;
use App\Entity\Famille;
use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;
use App\Repository\ClassementRepository;
use App\Repository\FamilleRepository;

class RessourcesSpecialiseesHabitationTexter implements RessourcesSpecialiseesEtablissementTexter {
    /** @var FamilleRepository */
    private $familleRepository;

    public function __construct(FamilleRepository $familleRepository) {
        $this->familleRepository = $familleRepository;
    }

    public function generateText(array $formData): string {
        $text = 'Habitation (';

        $familles = 'Toutes les familles';

        if (array_key_exists('familles', $formData) && is_array($formData['familles'])) {
            $famillesIds = $formData['familles'];

            /** @var array<int, ?Famille> $famillesEntities */
            $famillesEntities = array_map(function(string $familleId) {
                return $this->familleRepository->find($familleId);
            }, $famillesIds);
            /** @var array<int, Famille> $famillesEntities */
            $famillesEntities = array_filter($famillesEntities);

            /** @var array<int, ?string> $famillesLibelles */
            $famillesLibelles = array_map(function(Famille $familleId) {
                return $familleId->getLibelle();
            }, $famillesEntities);
            /** @var array<int, string> $famillesLibelles */
            $famillesLibelles = array_filter($famillesLibelles);

            $familles = implode('-', $famillesLibelles);
        }

        $text .= $familles.' - ';

        $groupements = filter_var($formData['groupements'], FILTER_VALIDATE_BOOLEAN);
        $commune = filter_var($formData['commune'], FILTER_VALIDATE_BOOLEAN);

        $text .= $groupements ? "Sur les groupements de l'utilisateur" : 'Ignorer les groupements';
        $text .= ' - ';
        $text .= $commune ? "Sur la commune de l'utilisateur" : 'Ignorer la commune';

        return $text;
    }
}
