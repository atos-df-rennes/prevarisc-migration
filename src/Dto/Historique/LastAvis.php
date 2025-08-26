<?php

namespace App\Dto\Historique;

use App\Entity\Avis;

class LastAvis
{
    /** @var ?Avis */
    private $avis;

    /** @var false|int */
    private $date;

    public function __construct()
    {
        $this->avis = null;
        $this->date = false;
    }

    public function setAvis(?Avis $avis): self
    {
        $this->avis = $avis;

        return $this;
    }

    public function getAvis(): ?Avis
    {
        return $this->avis;
    }

    /**
     * @param false|int $date
     */
    public function setDate($date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return false|int
     */
    public function getDate()
    {
        return $this->date;
    }
}
