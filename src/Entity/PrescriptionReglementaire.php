<?php

namespace App\Entity;

use App\Repository\PrescriptionReglementaireRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PrescriptionReglementaireRepository::class)
 *
 * @ORM\Table(name="prescriptionregl")
 */
class PrescriptionReglementaire
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_PRESCRIPTIONREGL", type="bigint")
     */
    private $id;

    /**
     * @var ?int
     *
     * @ORM\Column(name="PRESCRIPTIONREGL_TYPE", type="integer", nullable=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="PRESCRIPTIONREGL_LIBELLE", type="text", nullable=false)
     */
    private $libelle;

    /**
     * @var bool
     *
     * @ORM\Column(name="PRESCRIPTIONREGL_VISIBLE", type="boolean", nullable=false)
     */
    private $visible;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(?int $type): self
    {
        $this->type = $type;

        return $this;
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

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }
}
