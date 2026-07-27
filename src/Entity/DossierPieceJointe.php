<?php

namespace App\Entity;

use App\Repository\DossierPieceJointeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DossierPieceJointeRepository::class)]
#[ORM\Table(name: 'dossierpj')]
class DossierPieceJointe
{
    #[ORM\Column(name: 'PJ_COMMISSION', type: 'boolean', nullable: false)]
    private ?bool $pieceJointeCommission = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: PieceJointe::class)]
    #[ORM\JoinColumn(name: 'ID_PIECEJOINTE', referencedColumnName: 'ID_PIECEJOINTE', nullable: false)]
    private ?PieceJointe $pieceJointe = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Dossier::class, inversedBy: 'piecesJointes')]
    #[ORM\JoinColumn(name: 'ID_DOSSIER', referencedColumnName: 'ID_DOSSIER', nullable: false)]
    private ?Dossier $dossier = null;

    public function isPieceJointeCommission(): ?bool
    {
        return $this->pieceJointeCommission;
    }

    public function setPieceJointeCommission(bool $pieceJointeCommission): self
    {
        $this->pieceJointeCommission = $pieceJointeCommission;

        return $this;
    }

    public function getPieceJointe(): ?PieceJointe
    {
        return $this->pieceJointe;
    }

    public function setPieceJointe(PieceJointe $pieceJointe): self
    {
        $this->pieceJointe = $pieceJointe;

        return $this;
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
}
