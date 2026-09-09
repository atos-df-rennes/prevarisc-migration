<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\EtablissementPieceJointeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtablissementPieceJointeRepository::class)]
#[ORM\Table(name: 'etablissementpj')]
class EtablissementPieceJointe
{
    #[ORM\Column(name: 'PLACEMENT_ETABLISSEMENTPJ', type: Types::INTEGER, nullable: true)]
    private ?int $placement = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Etablissement::class, inversedBy: 'piecesJointes')]
    #[ORM\JoinColumn(name: 'ID_ETABLISSEMENT', referencedColumnName: 'ID_ETABLISSEMENT', nullable: false, options: ['unsigned' => true])]
    private ?Etablissement $etablissement = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: PieceJointe::class)]
    #[ORM\JoinColumn(name: 'ID_PIECEJOINTE', referencedColumnName: 'ID_PIECEJOINTE', nullable: false)]
    private ?PieceJointe $pieceJointe = null;

    public function getPlacement(): ?int
    {
        return $this->placement;
    }

    public function setPlacement(?int $placement): self
    {
        $this->placement = $placement;

        return $this;
    }

    public function getEtablissement(): ?Etablissement
    {
        return $this->etablissement;
    }

    public function setEtablissement(?Etablissement $etablissement): self
    {
        $this->etablissement = $etablissement;

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
}
