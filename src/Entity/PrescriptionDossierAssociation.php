<?php

namespace App\Entity;

use App\Repository\PrescriptionDossierAssociationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PrescriptionDossierAssociationRepository::class)
 *
 * @ORM\Table(name="prescriptiondossierassoc")
 */
class PrescriptionDossierAssociation
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\Column(name="NUM_PRESCRIPTION_DOSSIERASSOC", type="integer", nullable=false)
     */
    private $numero;

    /**
     * @var ?PrescriptionDossier
     *
     * @ORM\Id
     *
     * @ORM\ManyToOne(targetEntity=PrescriptionDossier::class, inversedBy="associations")
     *
     * @ORM\JoinColumn(name="ID_PRESCRIPTION_DOSSIER", referencedColumnName="ID_PRESCRIPTION_DOSSIER", nullable=false)
     */
    private $prescriptionDossier;

    /**
     * @var ?PrescriptionTexteListe
     *
     * @ORM\ManyToOne(targetEntity=PrescriptionTexteListe::class)
     *
     * @ORM\JoinColumn(name="ID_TEXTE", referencedColumnName="ID_TEXTE", nullable=true)
     */
    private $texte;

    /**
     * @var ?PrescriptionArticleListe
     *
     * @ORM\ManyToOne(targetEntity=PrescriptionArticleListe::class)
     *
     * @ORM\JoinColumn(name="ID_ARTICLE", referencedColumnName="ID_ARTICLE", nullable=true)
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

    public function getPrescriptionDossier(): ?PrescriptionDossier
    {
        return $this->prescriptionDossier;
    }

    public function setPrescriptionDossier(?PrescriptionDossier $prescriptionDossier): self
    {
        $this->prescriptionDossier = $prescriptionDossier;

        return $this;
    }

    public function getTexte(): ?PrescriptionTexteListe
    {
        return $this->texte;
    }

    public function setTexte(?PrescriptionTexteListe $texte): self
    {
        $this->texte = $texte;

        return $this;
    }

    public function getArticle(): ?PrescriptionArticleListe
    {
        return $this->article;
    }

    public function setArticle(?PrescriptionArticleListe $article): self
    {
        $this->article = $article;

        return $this;
    }
}
