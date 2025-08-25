<?php

namespace App\Entity;

use App\Repository\PrescriptionTypeAssociationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PrescriptionTypeAssociationRepository::class)
 *
 * @ORM\Table(name="prescriptiontypeassoc")
 */
class PrescriptionTypeAssociation
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
     * @var ?PrescriptionType
     *
     * @ORM\ManyToOne(targetEntity=PrescriptionType::class, inversedBy="associations")
     *
     * @ORM\JoinColumn(name="ID_PRESCRIPTIONTYPE", referencedColumnName="ID_PRESCRIPTIONTYPE", nullable=false)
     */
    private $prescriptionType;

    /**
     * @var PrescriptionTexteListe
     *
     * @ORM\ManyToOne(targetEntity=PrescriptionTexteListe::class)
     *
     * @ORM\JoinColumn(name="ID_TEXTE", referencedColumnName="ID_TEXTE", nullable=false)
     */
    private $texte;

    /**
     * @var PrescriptionArticleListe
     *
     * @ORM\ManyToOne(targetEntity=PrescriptionArticleListe::class)
     *
     * @ORM\JoinColumn(name="ID_ARTICLE", referencedColumnName="ID_ARTICLE", nullable=false)
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

    public function getPrescriptionType(): ?PrescriptionType
    {
        return $this->prescriptionType;
    }

    public function setPrescriptionType(?PrescriptionType $prescriptionType): self
    {
        $this->prescriptionType = $prescriptionType;

        return $this;
    }

    public function getTexte(): ?PrescriptionTexteListe
    {
        return $this->texte;
    }

    public function setTexte(PrescriptionTexteListe $texte): self
    {
        $this->texte = $texte;

        return $this;
    }

    public function getArticle(): ?PrescriptionArticleListe
    {
        return $this->article;
    }

    public function setArticle(PrescriptionArticleListe $article): self
    {
        $this->article = $article;

        return $this;
    }
}
