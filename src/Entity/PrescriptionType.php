<?php

namespace App\Entity;

use App\Repository\PrescriptionTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrescriptionTypeRepository::class)]
#[ORM\Table(name: 'prescriptiontype')]
class PrescriptionType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_PRESCRIPTIONTYPE', type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'PRESCRIPTIONTYPE_CATEGORIE', type: Types::BIGINT, nullable: true)]
    private ?int $categorie = null;

    #[ORM\Column(name: 'PRESCRIPTIONTYPE_TEXTE', type: Types::BIGINT, nullable: true)]
    private ?int $texte = null;

    #[ORM\Column(name: 'PRESCRIPTIONTYPE_ARTICLE', type: Types::BIGINT, nullable: true)]
    private ?int $article = null;

    #[ORM\Column(name: 'PRESCRIPTIONTYPE_LIBELLE', type: Types::TEXT, nullable: true)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'PRESCRIPTIONTYPE_NUM', type: Types::INTEGER, nullable: true)]
    private ?int $numero = null;

    /**
     * @var Collection<int, PrescriptionTypeAssociation>
     */
    #[ORM\OneToMany(targetEntity: PrescriptionTypeAssociation::class, mappedBy: 'prescriptionType', orphanRemoval: true, cascade: ['persist'])]
    private Collection $associations;

    public function __construct()
    {
        $this->associations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategorie(): ?int
    {
        return $this->categorie;
    }

    public function setCategorie(?int $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getTexte(): ?int
    {
        return $this->texte;
    }

    public function setTexte(?int $texte): self
    {
        $this->texte = $texte;

        return $this;
    }

    public function getArticle(): ?int
    {
        return $this->article;
    }

    public function setArticle(?int $article): self
    {
        $this->article = $article;

        return $this;
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

    public function setNumero(?int $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * @return Collection<int, PrescriptionTypeAssociation>
     */
    public function getAssociations(): Collection
    {
        return $this->associations;
    }

    /**
     * @param Collection<int, PrescriptionTypeAssociation> $associations
     */
    public function setAssociations(Collection $associations): self
    {
        $this->associations->clear();

        foreach ($associations as $key => $association) {
            $association->setNumero($key + 1);
            $association->setPrescriptionType($this);

            $this->associations->add($association);
        }

        return $this;
    }

    public function addAssociation(PrescriptionTypeAssociation $association): self
    {
        if (!$this->associations->contains($association)) {
            if (($lastAsso = $this->associations->last()) instanceof PrescriptionTypeAssociation) {
                $lastNumero = $lastAsso->getNumero();
            } else {
                $lastNumero = $this->associations->count();
            }

            $association->setNumero($lastNumero + 1);
            $association->setPrescriptionType($this);

            $this->associations->add($association);
        }

        return $this;
    }

    public function removeAssociation(PrescriptionTypeAssociation $association): self
    {
        // set the owning side to null (unless already changed)
        if ($this->associations->removeElement($association) && $association->getPrescriptionType() === $this) {
            $association->setPrescriptionType(null);
        }

        return $this;
    }
}
