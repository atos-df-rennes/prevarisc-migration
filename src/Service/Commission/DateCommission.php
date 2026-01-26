<?php

namespace App\Service\Commission;

use App\Entity\DateCommission as DateCommissionEntity;
use App\Entity\Dossier;
use App\Repository\DateCommissionRepository;

final class DateCommission
{
    /** @var DateCommissionRepository */
    private $dateCommissionRepository;

    public function __construct(DateCommissionRepository $dateCommissionRepository)
    {
        $this->dateCommissionRepository = $dateCommissionRepository;
    }

    /**
     * Retourne les dates de commission liées à la date de commission principale d'un dossier.
     *
     * @return DateCommissionEntity[]
     */
    public function getDatesLiees(Dossier $dossier): array
    {
        $dateVisite = $dossier->getDateVisite();

        if (null === $dateVisite) {
            return [];
        }

        $dateCommissionPrincipale = $this->dateCommissionRepository->findForDossier($dossier, $dateVisite);

        if (null == $dateCommissionPrincipale) {
            return [];
        }

        return $this->dateCommissionRepository->findDatesLiees($dateCommissionPrincipale);
    }
}
