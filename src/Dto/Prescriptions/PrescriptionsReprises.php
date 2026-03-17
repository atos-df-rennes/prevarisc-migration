<?php

namespace App\Dto\Prescriptions;

final class PrescriptionsReprises
{
    /** @var int */
    private $nombrePrescriptions = 0;

    /** @var int */
    private $nombreDossiers = 0;

    public function incrementNombrePrescriptions(): void
    {
        $this->nombrePrescriptions++;
    }

    public function incrementNombreDossiers(): void
    {
        $this->nombreDossiers++;
    }

    public function nombrePrescriptions(): int
    {
        return $this->nombrePrescriptions;
    }

    public function nombreDossiers(): int
    {
        return $this->nombreDossiers;
    }
}
