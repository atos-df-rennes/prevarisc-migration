<?php

namespace App\Entity;

use App\Repository\PrescriptionTexteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PrescriptionTexteRepository::class)
 *
 * @ORM\Table(name="prescriptiontexte")
 */
class PrescriptionTexte
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_PRESCRIPTIONTEXTE", type="bigint")
     */
    private $id;

    /**
     * @var ?string
     *
     * @ORM\Column(name="LIBELLE_PRESCRIPTIONTEXTE", type="string", length=255, nullable=true)
     */
    private $libelle;

    /**
     * @var int
     *
     * @ORM\Column(name="NUM_PRESCRIPTIONTEXTE", type="integer", nullable=false)
     */
    private $numero;

    /**
     * @var ?PrescriptionCategorie
     *
     * @ORM\ManyToOne(targetEntity=PrescriptionCategorie::class, inversedBy="textes")
     *
     * @ORM\JoinColumn(name="ID_PRESCRIPTIONCAT", referencedColumnName="ID_PRESCRIPTION_CAT", nullable=false)
     */
    private $categorie;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): self
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

    public function getCategorie(): ?PrescriptionCategorie
    {
        return $this->categorie;
    }

    public function setCategorie(?PrescriptionCategorie $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }
}
