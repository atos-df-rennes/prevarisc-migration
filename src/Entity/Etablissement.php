<?php

namespace App\Entity;

use App\Repository\EtablissementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EtablissementRepository::class)
 *
 * @ORM\Table(indexes={@ORM\Index(columns={"DATESUPPRESSION_ETABLISSEMENT"})})
 */
class Etablissement
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_ETABLISSEMENT", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATESUPPRESSION_ETABLISSEMENT", type="date", nullable=true)
     */
    private $dateSuppression;

    /**
     * @var ?Utilisateur
     *
     * @ORM\ManyToOne(targetEntity=Utilisateur::class)
     *
     * @ORM\JoinColumn(name="DELETED_BY", referencedColumnName="ID_UTILISATEUR", options={"unsigned":true})
     */
    private $deletedBy;

    /**
     * @var Collection<int, EtablissementInformations>
     *
     * @ORM\OneToMany(targetEntity=EtablissementInformations::class, mappedBy="etablissement")
     */
    private $informations;

    /**
     * @var Collection<int, Dossier>
     *
     * @ORM\ManyToMany(targetEntity=Dossier::class, mappedBy="etablissements")
     */
    private $dossiers;

    /**
     * @var Collection<int, EtablissementAdresse>
     *
     * @ORM\OneToMany(targetEntity=EtablissementAdresse::class, mappedBy="etablissement", orphanRemoval=true)
     */
    private $adresses;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="NUMEROID_ETABLISSEMENT", type="string", length=50, nullable=true)
     */
    private $numeroIdEtablissement;

    /**
     * @var ?Dossier
     * 
     * @ORM\OneToOne(targetEntity=Dossier::class, cascade={"persist", "remove"})
     * 
     * @ORM\JoinColumn(name="ID_DOSSIER_DONNANT_AVIS", referencedColumnName="ID_DOSSIER", nullable=true)
     */
    private $idDossierDonnantAvis;

    /**
     * @var Collection<int, EtablissementLie>
     * 
     * @ORM\OneToMany(targetEntity=EtablissementLie::class, mappedBy="etablissement", cascade={"persist", "remove"})
     */
    private $etablissementsFilsLiees;

    /**
     * @var Collection<int, EtablissementLie>
     * 
     * @ORM\OneToMany(targetEntity=EtablissementLie::class, mappedBy="filsEtablissement", cascade={"persist", "remove"})
     */
    private $etablissementsParentsLiees;

    public function __construct()
    {
        $this->dossiers = new ArrayCollection();
        $this->adresses = new ArrayCollection();
        $this->etablissementsFilsLiees = new ArrayCollection();
        $this->etablissementsParentsLiees = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateSuppression(): ?\DateTimeInterface
    {
        return $this->dateSuppression;
    }

    public function setDateSuppression(?\DateTimeInterface $dateSuppression): self
    {
        $this->dateSuppression = $dateSuppression;

        return $this;
    }

    public function getDeletedBy(): ?Utilisateur
    {
        return $this->deletedBy;
    }

    public function setDeletedBy(?Utilisateur $deletedBy): self
    {
        $this->deletedBy = $deletedBy;

        return $this;
    }

    /**
     * @return Collection<int, EtablissementInformations>
     */
    public function getInformations(): Collection
    {
        return $this->informations;
    }

    public function addInformations(EtablissementInformations $informations): self
    {
        if (!$this->informations->contains($informations)) {
            $this->informations[] = $informations;
            $informations->setEtablissement($this);
        }

        return $this;
    }

    public function removeInformations(EtablissementInformations $informations): self
    {
        // set the owning side to null (unless already changed)
        if ($this->informations->removeElement($informations) && $informations->getEtablissement() === $this) {
            $informations->setEtablissement(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Dossier>
     */
    public function getDossiers(): Collection
    {
        return $this->dossiers;
    }

    public function addDossier(Dossier $dossier): self
    {
        if (!$this->dossiers->contains($dossier)) {
            $this->dossiers[] = $dossier;
            $dossier->addEtablissement($this);
        }

        return $this;
    }

    public function removeDossier(Dossier $dossier): self
    {
        if ($this->dossiers->removeElement($dossier)) {
            $dossier->removeEtablissement($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, EtablissementAdresse>
     */
    public function getAdresses(): Collection
    {
        return $this->adresses;
    }

    public function addAdress(EtablissementAdresse $adress): self
    {
        if (!$this->adresses->contains($adress)) {
            $this->adresses[] = $adress;
            $adress->setEtablissement($this);
        }

        return $this;
    }

    public function removeAdress(EtablissementAdresse $adress): self
    {
        // set the owning side to null (unless already changed)
        if ($this->adresses->removeElement($adress) && $adress->getEtablissement() === $this) {
            $adress->setEtablissement(null);
        }

        return $this;
    }

    public function getNumeroIdEtablissement(): ?string
    {
        return $this->numeroIdEtablissement;
    }

    public function setNumeroIdEtablissement(?string $numeroIdEtablissement): self
    {
        $this->numeroIdEtablissement = $numeroIdEtablissement;

        return $this;
    }

    public function getIdDossierDonnantAvis(): ?Dossier
    {
        return $this->idDossierDonnantAvis;
    }

    public function setIdDossierDonnantAvis(?Dossier $idDossierDonnantAvis): self
    {
        $this->idDossierDonnantAvis = $idDossierDonnantAvis;

        return $this;
    }
      
    public function getEtablissementsFilsLiees(): Collection
    {
        return $this->etablissementsFilsLiees;
    }

    public function getEtablissementsParentsLiees(): Collection
    {
        return $this->etablissementsParentsLiees;
    }

}
