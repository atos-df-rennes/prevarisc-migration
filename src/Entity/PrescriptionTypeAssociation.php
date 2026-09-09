<?php

namespace App\Entity;

use App\Repository\PrescriptionTypeAssociationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrescriptionTypeAssociationRepository::class)]
#[ORM\Table(name: 'prescriptiontypeassoc')]
class PrescriptionTypeAssociation
{
    #[ORM\Id]
    #[ORM\Column(name: 'NUM_PRESCRIPTIONASSOC', type: Types::INTEGER, nullable: false)]
    private ?int $numero = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: PrescriptionType::class, inversedBy: 'associations')]
    #[ORM\JoinColumn(name: 'ID_PRESCRIPTIONTYPE', referencedColumnName: 'ID_PRESCRIPTIONTYPE', nullable: false)]
    private ?PrescriptionType $prescriptionType = null;

    #[ORM\ManyToOne(targetEntity: PrescriptionTexteListe::class)]
    #[ORM\JoinColumn(name: 'ID_TEXTE', referencedColumnName: 'ID_TEXTE', nullable: false)]
    private ?PrescriptionTexteListe $texte = null;

    #[ORM\ManyToOne(targetEntity: PrescriptionArticleListe::class)]
    #[ORM\JoinColumn(name: 'ID_ARTICLE', referencedColumnName: 'ID_ARTICLE', nullable: false)]
    private ?PrescriptionArticleListe $article = null;

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
