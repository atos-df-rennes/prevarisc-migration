<?php

namespace App\Entity;

use App\Repository\PrescriptionCategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrescriptionCategorieRepository::class)]
#[ORM\Table(name: 'prescriptioncat')]
class PrescriptionCategorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_PRESCRIPTION_CAT', type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'LIBELLE_PRESCRIPTION_CAT', type: Types::STRING, length: 255, nullable: false)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'NUM_PRESCRIPTION_CAT', type: Types::INTEGER, nullable: false)]
    private ?int $numero = null;

    /**
     * @var Collection<int, PrescriptionTexte>
     */
    #[ORM\OneToMany(targetEntity: PrescriptionTexte::class, mappedBy: 'categorie', orphanRemoval: true)]
    private Collection $textes;

    public function __construct()
    {
        $this->textes = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, PrescriptionTexte>
     */
    public function getTextes(): Collection
    {
        return $this->textes;
    }

    public function addTexte(PrescriptionTexte $texte): self
    {
        if (!$this->textes->contains($texte)) {
            $this->textes[] = $texte;
            $texte->setCategorie($this);
        }

        return $this;
    }

    public function removeTexte(PrescriptionTexte $texte): self
    {
        // set the owning side to null (unless already changed)
        if ($this->textes->removeElement($texte) && $texte->getCategorie() === $this) {
            $texte->setCategorie(null);
        }

        return $this;
    }
}
