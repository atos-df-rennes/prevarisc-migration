<?php

namespace App\Service;

use App\Entity\Dossier;
use App\Entity\PrescriptionDossier;
use App\Repository\PrescriptionDossierRepository;

class DossierPrescriptionService
{
    /** @var PrescriptionDossierRepository */
    private $repository;

    public function __construct(PrescriptionDossierRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Retourne les prescriptions d'un dossier pour un type donné.
     *
     * 0 = Rappels réglementaires
     * 1 = Prescriptions liées à l'exploitation
     * 2 = Recommandations liées à l'amélioration
     *
     * @return PrescriptionDossier[]
     */
    public function getPrescriptionsParType(Dossier $dossier, int $type): array
    {
        return $this->repository->findByDossierAndType($dossier, $type);
    }
}
