<?php

namespace App\Entity;

use App\Repository\EtablissementInformationsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EtablissementInformationsRepository::class)
 *
 * @ORM\Table(name="etablissementinformations")
 */
class EtablissementInformations
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_ETABLISSEMENTINFORMATIONS", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_ETABLISSEMENTINFORMATIONS", type="string", length=255, nullable=false)
     */
    private $libelle;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="DATE_ETABLISSEMENTINFORMATIONS", type="date", nullable=false)
     */
    private $date;

    /**
     * @var ?Etablissement
     *
     * @ORM\ManyToOne(targetEntity=Etablissement::class, inversedBy="informations")
     *
     * @ORM\JoinColumn(name="ID_ETABLISSEMENT", referencedColumnName="ID_ETABLISSEMENT", nullable=false, options={"unsigned":true})
     */
    private $etablissement;

    /**
     * @var EtablissementStatut
     *
     * @ORM\ManyToOne(targetEntity=EtablissementStatut::class)
     *
     * @ORM\JoinColumn(name="ID_STATUT", referencedColumnName="ID_STATUT", nullable=false, options={"unsigned":true})
     */
    private $statut;

    /**
     * @var ?EtablissementType
     *
     * @ORM\ManyToOne(targetEntity=EtablissementType::class)
     *
     * @ORM\JoinColumn(name="ID_TYPE", referencedColumnName="ID_TYPE", nullable=true, options={"unsigned":true})
     */
    private $type;

    /**
     * @var ?EtablissementCategorie
     *
     * @ORM\ManyToOne(targetEntity=EtablissementCategorie::class)
     *
     * @ORM\JoinColumn(name="ID_CATEGORIE", referencedColumnName="ID_CATEGORIE", nullable=true, options={"unsigned":true})
     */
    private $categorie;

    /**
     * @var ?IghClasse
     *
     * @ORM\ManyToOne(targetEntity=IghClasse::class)
     *
     * @ORM\JoinColumn(name="ID_CLASSE", referencedColumnName="ID_CLASSE", nullable=true, options={"unsigned":true})
     */
    private $classe;

    /**
     * @var ?Famille
     *
     * @ORM\ManyToOne(targetEntity=Famille::class)
     *
     * @ORM\JoinColumn(name="ID_FAMILLE", referencedColumnName="ID_FAMILLE", nullable=true)
     */
    private $famille;

    /**
     * @var ?UtilisateurInformations
     *
     * @ORM\ManyToOne(targetEntity=UtilisateurInformations::class)
     *
     * @ORM\JoinColumn(name="UTILISATEUR_ETABLISSEMENTINFORMATIONS", referencedColumnName="ID_UTILISATEURINFORMATIONS", nullable=true, options={"unsigned":true})
     */
    private $utilisateur;

    /**
     * @var Genre
     *
     * @ORM\ManyToOne(targetEntity=Genre::class)
     *
     * @ORM\JoinColumn(name="ID_GENRE", referencedColumnName="ID_GENRE", nullable=false, options={"unsigned":true})
     */
    private $genre;

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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getEtablissement(): ?Etablissement
    {
        return $this->etablissement;
    }

    public function setEtablissement(?Etablissement $etablissement): self
    {
        $this->etablissement = $etablissement;

        return $this;
    }

    public function getStatut(): ?EtablissementStatut
    {
        return $this->statut;
    }

    public function setStatut(EtablissementStatut $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getType(): ?EtablissementType
    {
        return $this->type;
    }

    public function setType(?EtablissementType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCategorie(): ?EtablissementCategorie
    {
        return $this->categorie;
    }

    public function setCategorie(?EtablissementCategorie $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getClasse(): ?IghClasse
    {
        return $this->classe;
    }

    public function setClasse(?IghClasse $classe): self
    {
        $this->classe = $classe;

        return $this;
    }

    public function getFamille(): ?Famille
    {
        return $this->famille;
    }

    public function setFamille(?Famille $famille): self
    {
        $this->famille = $famille;

        return $this;
    }

    public function getUtilisateur(): ?UtilisateurInformations
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?UtilisateurInformations $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getGenre(): ?Genre
    {
        return $this->genre;
    }

    public function setGenre(Genre $genre): self
    {
        $this->genre = $genre;

        return $this;
    }
}
