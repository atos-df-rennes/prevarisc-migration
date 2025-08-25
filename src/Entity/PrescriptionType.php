<?php

namespace App\Entity;

use App\Repository\PrescriptionTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PrescriptionTypeRepository::class)
 *
 * @ORM\Table(name="prescriptiontype")
 */
class PrescriptionType
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_PRESCRIPTIONTYPE", type="bigint")
     */
    private $id;

    /**
     * @var ?int
     *
     * @ORM\Column(name="PRESCRIPTIONTYPE_CATEGORIE", type="bigint", nullable=true)
     */
    private $categorie;

    /**
     * @var ?int
     *
     * @ORM\Column(name="PRESCRIPTIONTYPE_TEXTE", type="bigint", nullable=true)
     */
    private $texte;

    /**
     * @var ?int
     *
     * @ORM\Column(name="PRESCRIPTIONTYPE_ARTICLE", type="bigint", nullable=true)
     */
    private $article;

    /**
     * @var ?string
     *
     * @ORM\Column(name="PRESCRIPTIONTYPE_LIBELLE", type="text", nullable=true)
     */
    private $libelle;

    /**
     * @var ?int
     *
     * @ORM\Column(name="PRESCRIPTIONTYPE_NUM", type="integer", nullable=true)
     */
    private $numero;

    /**
     * @var Collection<int, PrescriptionTypeAssociation>
     *
     * @ORM\OneToMany(targetEntity=PrescriptionTypeAssociation::class, mappedBy="prescriptionType", orphanRemoval=true)
     */
    private $associations;

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

    public function addAssociation(PrescriptionTypeAssociation $association): self
    {
        if (!$this->associations->contains($association)) {
            $this->associations[] = $association;
            $association->setPrescriptionType($this);
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
