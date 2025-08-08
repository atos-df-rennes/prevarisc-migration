<?php

namespace App\Entity;

use App\Repository\PrestationTexteListeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PrestationTexteListeRepository::class)
 *
 * @ORM\Table(name="prescriptiontexteliste")
 */
class PrestationTexteListe
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     * @ORM\Column(name="ID_TEXTE", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_TEXTE", type="string", length=255)
     */
    private $libelle;

    /**
     * @var ?bool
     *
     * @ORM\Column(name="VISIBLE_TEXTE", type="boolean", nullable=true)
     */
    private $visible;

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

    public function getVisible(): ?bool
    {
        return $this->visible;
    }

    public function setVisible(?bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }
}
