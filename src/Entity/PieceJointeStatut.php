<?php

namespace App\Entity;

use App\Repository\PieceJointeStatutRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PieceJointeStatutRepository::class)
 */
class PieceJointeStatut
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="ID_PIECEJOINTESTATUT", type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="NOM_STATUT", type="string", length=50, nullable=true)
     */
    private $nomStatut;

    /**
     * @ORM\Column(name="NOM_LISIBLE", type="string", length=50)
     */
    private $nomLisible;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nomStatut;
    }

    public function setNom(?string $nom): self
    {
        $this->nomStatut = $nom;

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
}
