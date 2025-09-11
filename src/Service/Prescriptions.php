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

    public function getTextesListe()
    {
        return $this->prescriptionTexteListeRepository->findAllTextes();
    }
}
