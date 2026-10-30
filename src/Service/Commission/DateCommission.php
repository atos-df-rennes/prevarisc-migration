<?php

namespace App\Service\Commission;

use App\Entity\DateCommission as DateCommissionEntity;
use App\Entity\Dossier;
use App\Repository\DateCommissionRepository;

final readonly class DateCommission
{
    public function __construct(private DateCommissionRepository $dateCommissionRepository)
    {
    }

    /**
     * Retourne les dates de commission liées à la date de commission principale d'un dossier.
     *
     * @return DateCommissionEntity[]
     */
    public function getDatesLiees(Dossier $dossier): array
    {
        $dateVisite = $dossier->getDateVisite();

        if (!$dateVisite instanceof \DateTimeInterface) {
            return [];
        }

        $dateCommissionPrincipale = $this->dateCommissionRepository->findForDossier($dossier, $dateVisite);

        if (!$dateCommissionPrincipale instanceof DateCommissionEntity) {
            return [];
        }

        return $this->dateCommissionRepository->findDatesLiees($dateCommissionPrincipale);
    }
}
