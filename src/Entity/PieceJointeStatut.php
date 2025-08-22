<?php

namespace App\Entity;

use App\Repository\PieceJointeStatutRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PieceJointeStatutRepository::class)
 *
 * @ORM\Table(name="piecejointestatut")
 */
class PieceJointeStatut
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_PIECEJOINTESTATUT", type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="NOM_STATUT", type="string", length=50, nullable=false)
     */
    private $nomStatut;

    /**
     * @var string
     *
     * @ORM\Column(name="NOM_LISIBLE", type="string", length=50, nullable=false)
     */
    private $nomLisible;

    /**
     * @var Collection<int, PieceJointe>
     *
     * @ORM\OneToMany(targetEntity=PieceJointe::class, mappedBy="statut")
     */
    private $pieceJointes;

    public function __construct()
    {
        $this->pieceJointes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomStatut(): ?string
    {
        return $this->nomStatut;
    }

    public function setNomStatut(string $nomStatut): self
    {
        $this->nomStatut = $nomStatut;

        return $this;
    }

    public function getNomLisible(): ?string
    {
        return $this->nomLisible;
    }

    public function setNomLisible(string $nomLisible): self
    {
        $this->nomLisible = $nomLisible;

        return $this;
    }

    /**
     * @return Collection<int, PieceJointe>
     */
    public function getPieceJointes(): Collection
    {
        return $this->pieceJointes;
    }

    public function addPieceJointe(PieceJointe $pieceJointe): self
    {
        if (!$this->pieceJointes->contains($pieceJointe)) {
            $this->pieceJointes[] = $pieceJointe;
            $pieceJointe->setStatut($this);
        }

        return $this;
    }

    public function removePieceJointe(PieceJointe $pieceJointe): self
    {
        // set the owning side to null (unless already changed)
        if ($this->pieceJointes->removeElement($pieceJointe) && $pieceJointe->getStatut() === $this) {
            $pieceJointe->setStatut(null);
        }

        return $this;
    }
}
