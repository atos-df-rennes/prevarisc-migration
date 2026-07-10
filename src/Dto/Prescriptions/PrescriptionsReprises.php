<?php

namespace App\Dto\Prescriptions;

final class PrescriptionsReprises
{
    private int $nombrePrescriptions = 0;

    private int $nombreDossiers = 0;

    public function incrementNombrePrescriptions(): void
    {
        ++$this->nombrePrescriptions;
    }

    public function incrementNombreDossiers(): void
    {
        ++$this->nombreDossiers;
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
