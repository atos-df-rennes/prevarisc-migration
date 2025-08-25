<?php

namespace App\Entity;

use App\Repository\PrescriptionReglementaireAssociationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PrescriptionReglementaireAssociationRepository::class)
 *
 * @ORM\Table(name="prescriptionreglassoc")
 */
class PrescriptionReglementaireAssociation
{
    /**
     * @ORM\Id
     *
     * @var int
     *
     * @ORM\Column(name="NUM_PRESCRIPTIONASSOC", type="integer", nullable=false)
     */
    private $numero;

    /**
     * @ORM\Id
     *
     * @var ?PrescriptionReglementaire
     *
     * @ORM\ManyToOne(targetEntity=PrescriptionReglementaire::class, inversedBy="associations")
     *
     * @ORM\JoinColumn(name="ID_PRESCRIPTIONREGL", referencedColumnName="ID_PRESCRIPTIONREGL", nullable=false)
     */
    private $regle;

    /**
     * @var int
     *
     * @ORM\Column(name="ID_TEXTE", type="bigint", nullable=false)
     */
    private $texte;

    /**
     * @var int
     *
     * @ORM\Column(name="ID_ARTICLE", type="bigint", nullable=false)
     */
    private $article;

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getRegle(): ?PrescriptionReglementaire
    {
        return $this->regle;
    }

    public function setRegle(?PrescriptionReglementaire $regle): self
    {
        $this->regle = $regle;

        return $this;
    }

    public function getTexte(): ?int
    {
        return $this->texte;
    }

    public function setTexte(int $texte): self
    {
        $this->texte = $texte;

        return $this;
    }

    public function getArticle(): ?int
    {
        return $this->article;
    }

    public function setArticle(int $article): self
    {
        $this->article = $article;

        return $this;
    }
}
