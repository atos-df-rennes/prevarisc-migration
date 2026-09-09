<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\PrescriptionTexteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrescriptionTexteRepository::class)]
#[ORM\Table(name: 'prescriptiontexte')]
class PrescriptionTexte
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_PRESCRIPTIONTEXTE', type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(name: 'LIBELLE_PRESCRIPTIONTEXTE', type: Types::STRING, length: 255, nullable: true)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'NUM_PRESCRIPTIONTEXTE', type: Types::INTEGER, nullable: false)]
    private ?int $numero = null;

    #[ORM\ManyToOne(targetEntity: PrescriptionCategorie::class, inversedBy: 'textes')]
    #[ORM\JoinColumn(name: 'ID_PRESCRIPTIONCAT', referencedColumnName: 'ID_PRESCRIPTION_CAT', nullable: false)]
    private ?PrescriptionCategorie $categorie = null;

    /**
     * @var Collection<int, PrescriptionArticle>
     */
    #[ORM\OneToMany(targetEntity: PrescriptionArticle::class, mappedBy: 'texte', orphanRemoval: true)]
    private Collection $articles;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, PrescriptionArticle>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(PrescriptionArticle $article): self
    {
        if (!$this->articles->contains($article)) {
            $this->articles[] = $article;
            $article->setTexte($this);
        }

        return $this;
    }

    public function removeArticle(PrescriptionArticle $article): self
    {
        // set the owning side to null (unless already changed)
        if ($this->articles->removeElement($article) && $article->getTexte() === $this) {
            $article->setTexte(null);
        }

        return $this;
    }
}
