<?php

namespace App\Entity;

use App\Repository\PrescriptionCategorieRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PrescriptionCategorieRepository::class)
 *
 * @ORM\Table(name="prescriptioncat")
 */
class PrescriptionCategorie
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_PRESCRIPTION_CAT", type="bigint")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_PRESCRIPTION_CAT", type="string", length=255, nullable=false)
     */
    private $libelle;

    /**
     * @var int
     *
     * @ORM\Column(name="NUM_PRESCRIPTION_CAT", type="integer", nullable=false)
     */
    private $numero;

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

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): self
    {
        $this->numero = $numero;

        return $this;
    }
}
