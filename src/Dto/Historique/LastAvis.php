<?php

namespace App\Dto\Historique;

use App\Entity\Avis;

class LastAvis
{
    private ?Avis $avis = null;

    private ?int $date = null;

    public function setAvis(?Avis $avis): self
    {
        $this->avis = $avis;

        return $this;
    }

    public function getAvis(): ?Avis
    {
        return $this->avis;
    }

    public function setDate(?int $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getDate(): ?int
    {
        return $this->date;
    }
}
