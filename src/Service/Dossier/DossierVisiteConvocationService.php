<?php

namespace App\Service\Dossier;

use App\Constant\CommissionTypeEvenement as TypeEvenement;
use App\Entity\Dossier;
use App\Repository\DateCommissionRepository;
use App\Repository\DossierAffectationRepository;
use App\Utils\DateFormatterFr;

/**
 * Calcule les libellés de date(s) et heure(s) de convocation (VP/VP-VR) d'un dossier.
 *
 * Port direct du comportement legacy (DossierController::creationdocAction) : une visite peut être
 * affectée à un groupe de dates de commission liées (ex: visite répartie sur plusieurs jours ou
 * plusieurs créneaux), il faut donc agréger l'ensemble du groupe pour reconstituer les champs
 * "dateVisite" et "heureVisite" plutôt que de se baser sur la seule colonne DATEVISITE_DOSSIER
 * (qui ne stocke qu'une date, sans heure).
 */
class DossierVisiteConvocationService
{
    /** @var DossierAffectationRepository */
    private $affectationRepository;

    /** @var DateCommissionRepository */
    private $dateCommissionRepository;

    public function __construct(
        DossierAffectationRepository $affectationRepository,
        DateCommissionRepository $dateCommissionRepository
    ) {
        $this->affectationRepository = $affectationRepository;
        $this->dateCommissionRepository = $dateCommissionRepository;
    }

    /**
     * Retourne les libellés de date(s) et heure(s) de convocation pour un dossier.
     *
     * Si le dossier n'est affecté à aucune date de visite/groupe de visite, les deux champs
     * sont retournés vides.
     *
     * @return array{dateVisite: string, heureVisite: string}
     */
    public function getInfosConvocation(Dossier $dossier): array
    {
        $affectation = $this->affectationRepository->findAffectationByDossierAndType(
            $dossier,
            [TypeEvenement::TYPE_VISITE, TypeEvenement::TYPE_GROUPE_VISITE]
        );

        $dateCommission = null !== $affectation ? $affectation->getDateCommission() : null;

        if (null === $dateCommission) {
            return ['dateVisite' => '', 'heureVisite' => ''];
        }

        $datesLiees = $this->dateCommissionRepository->findGroupeDatesLiees($dateCommission);

        $libellesDates = [];
        $libellesHeures = [];

        foreach ($datesLiees as $dateCommissionLiee) {
            $date = $dateCommissionLiee->getDate();
            $heureDebut = $dateCommissionLiee->getHeureDebut();
            $heureFin = $dateCommissionLiee->getHeureFin();

            if (null !== $date) {
                $libellesDates[] = DateFormatterFr::formatLong($date);
            }

            if (null !== $heureDebut && null !== $heureFin) {
                $libellesHeures[] = $heureDebut->format('H:i').' à '.$heureFin->format('H:i');
            }
        }

        return [
            'dateVisite' => implode(', ', $libellesDates),
            'heureVisite' => implode(', ', $libellesHeures),
        ];
    }
}
