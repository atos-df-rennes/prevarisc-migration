<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\ChangementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChangementRepository::class)]
class Changement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_CHANGEMENT', type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'LIBELLE_CHANGEMENT', type: Types::STRING, length: 255, nullable: false)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'MESSAGE_CHANGEMENT', type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }
}
