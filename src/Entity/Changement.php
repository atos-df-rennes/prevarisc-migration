<?php

namespace App\Entity;

use App\Repository\ChangementRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ChangementRepository::class)
 */
class Changement
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="ID_CHANGEMENT", type="integer", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_CHANGEMENT", type="string", length=255)
     */
    private $libelle;

    /**
     * @var ?string
     *
     * @ORM\Column(name="MESSAGE_CHANGEMENT", type="text", nullable=true)
     */
    private $message;

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
