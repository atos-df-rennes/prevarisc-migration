<?php

namespace App\Entity;

use App\Repository\DossierPJRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DossierPJRepository::class)
 */
class DossierPJ
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Dossier::class)
     * @ORM\JoinColumn(name="ID_DOSSIER", referencedColumnName="ID_DOSSIER", nullable=false)     */
    private $dossier;

    /**
     * @ORM\ManyToOne(targetEntity=Piecejointe::class)
     * @ORM\JoinColumn(name="ID_PIECEJOINTE", referencedColumnName="ID_PIECEJOINTE", nullable=false)
     */
    private $pieceJointe;

    /**
     * @ORM\Column(name="PJ_COMMISSION", type="boolean")
     */
    private $pjCommission;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDossier(): ?Dossier
    {
        return $this->dossier;
    }

    public function setDossier(?Dossier $dossier): self
    {
        $this->dossier = $dossier;
        return $this;
    }

    public function getPieceJointe(): ?Piecejointe
    {
        return $this->pieceJointe;
    }

    public function setPieceJointe(?Piecejointe $pieceJointe): self
    {
        $this->pieceJointe = $pieceJointe;
        return $this;
    }

    public function getPjCommission(): ?bool
    {
        return $this->pjCommission;
    }

    public function setPjCommission(bool $pjCommission): self
    {
        $this->pjCommission = $pjCommission;
        return $this;
    }
}
