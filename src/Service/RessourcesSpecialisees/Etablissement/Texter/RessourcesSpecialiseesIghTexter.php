<?php

namespace App\Service\RessourcesSpecialisees\Etablissement\Texter;

use App\Entity\Classement;
use App\Entity\IghClasse;
use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;
use App\Repository\ClassementRepository;
use App\Repository\FamilleRepository;
use App\Repository\IghClasseRepository;

class RessourcesSpecialiseesIghTexter implements RessourcesSpecialiseesEtablissementTexter {
    /** @var \App\Repository\IghClasseRepository */
    private $classeRepository;

    public function __construct(IghClasseRepository $classeRepository) {
        $this->classeRepository = $classeRepository;
    }

    public function generateText(array $formData): string {
        $text = 'IGH (';

        $classes = 'Toutes les classes';

        if (array_key_exists('classes', $formData) && is_array($formData['classes'])) {
            $classesIds = $formData['classes'];

            /** @var array<int, ?IghClasse> $classesEntities */
            $classesEntities = array_map(function(string $classeId) {
                return $this->classeRepository->find($classeId);
            }, $classesIds);
            /** @var array<int, IghClasse> $classesEntities */
            $classesEntities = array_filter($classesEntities);

            /** @var array<int, ?string> $classesLibelles */
            $classesLibelles = array_map(function(IghClasse $classeId) {
                return $classeId->getLibelle();
            }, $classesEntities);
            /** @var array<int, string> $classesLibelles */
            $classesLibelles = array_filter($classesLibelles);

            $classes = implode('-', $classesLibelles);
        }

        $text .= $classes.' - ';

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
