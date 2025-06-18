<?php

namespace App\Entity;

use App\Repository\PiecejointeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PiecejointeRepository::class)
 */
class Piecejointe
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="ID_PIECEJOINTE", type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="NOM_PIECEJOINTE", type="string", length=255)
     */
    private $nom;

    /**
     * @ORM\Column(name="EXTENSION_PIECEJOINTE", type="string", length=10)
     */
    private $extension;

    /**
     * @ORM\Column(name="DESCRIPTION_PIECEJOINTE", type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity=PieceJointeStatut::class)
     * @ORM\JoinColumn(name="ID_PIECEJOINTESTATUT", referencedColumnName="ID_PIECEJOINTESTATUT")
     */
    private $statut;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getStatut(): ?PieceJointeStatut
    {
        return $this->statut;
    }

    public function setStatut(?PieceJointeStatut $statut): self
    {
        $this->statut = $statut;
        return $this;
    }


}
