<?php

namespace App\Entity;

use App\Repository\PieceJointeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PieceJointeRepository::class)]
#[ORM\Table(name: 'piecejointe')]
class PieceJointe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_PIECEJOINTE', type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'NOM_PIECEJOINTE', type: Types::STRING, length: 255, nullable: false)]
    private ?string $nom = null;

    #[ORM\Column(name: 'EXTENSION_PIECEJOINTE', type: Types::STRING, length: 10, nullable: false)]
    private ?string $extension = null;

    #[ORM\Column(name: 'DESCRIPTION_PIECEJOINTE', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'DATE_PIECEJOINTE', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(targetEntity: PieceJointeStatut::class, inversedBy: 'pieceJointes')]
    #[ORM\JoinColumn(name: 'ID_PIECEJOINTESTATUT', referencedColumnName: 'ID_PIECEJOINTESTATUT')]
    private ?PieceJointeStatut $statut = null;

    #[ORM\Column(name: 'ID_PLATAU', type: Types::STRING, length: 11, nullable: true, options: ['fixed' => true])]
    private ?string $identifiantPlatau = null;

    #[ORM\Column(name: 'MESSAGE_ERREUR', type: Types::STRING, length: 255, nullable: true)]
    private ?string $messageErreur = null;

    #[ORM\Column(name: 'DATE_NOTIFICATION', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateNotification = null;

    #[ORM\Column(name: 'TYPE', type: Types::TEXT, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(name: 'SOUS_TYPE', type: Types::TEXT, nullable: true)]
    private ?string $sousType = null;

    #[ORM\Column(name: 'NATURE', type: Types::STRING, length: 255, nullable: true)]
    private ?string $nature = null;

    #[ORM\Column(name: 'DATE_DEPOT', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDepot = null;

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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

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

    public function getIdentifiantPlatau(): ?string
    {
        return $this->identifiantPlatau;
    }

    public function setIdentifiantPlatau(?string $identifiantPlatau): self
    {
        $this->identifiantPlatau = $identifiantPlatau;

        return $this;
    }

    public function getMessageErreur(): ?string
    {
        return $this->messageErreur;
    }

    public function setMessageErreur(?string $messageErreur): self
    {
        $this->messageErreur = $messageErreur;

        return $this;
    }

    public function getDateNotification(): ?\DateTimeInterface
    {
        return $this->dateNotification;
    }

    public function setDateNotification(?\DateTimeInterface $dateNotification): self
    {
        $this->dateNotification = $dateNotification;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getSousType(): ?string
    {
        return $this->sousType;
    }

    public function setSousType(?string $sousType): self
    {
        $this->sousType = $sousType;

        return $this;
    }

    public function getNature(): ?string
    {
        return $this->nature;
    }

    public function setNature(?string $nature): self
    {
        $this->nature = $nature;

        return $this;
    }

    public function getDateDepot(): ?\DateTimeInterface
    {
        return $this->dateDepot;
    }

    public function setDateDepot(?\DateTimeInterface $dateDepot): self
    {
        $this->dateDepot = $dateDepot;

        return $this;
    }
}
