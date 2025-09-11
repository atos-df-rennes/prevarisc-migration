<?php

namespace App\Service;

use App\Repository\PrescriptionTexteListeRepository;

final class Prescriptions
{
    /** @var PrescriptionTexteListeRepository */
    private $prescriptionTexteListeRepository;

    public function __construct(PrescriptionTexteListeRepository $prescriptionTexteListeRepository)
    {
        $this->prescriptionTexteListeRepository = $prescriptionTexteListeRepository;
    }

    /**
     * @return \App\Entity\PrescriptionTexteListe[]
     */
    public function getTextesListe(): array
    {
        return $this->prescriptionTexteListeRepository->findAllTextes();
    }
}
