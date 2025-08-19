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
    private $numero;

    /**
     * @var Collection<int, Etablissement>
     *
     * @ORM\JoinTable(
     *     name="etablissementlie",
     *     joinColumns={@ORM\JoinColumn(name="ID_ETABLISSEMENT", referencedColumnName="ID_ETABLISSEMENT", options={"unsigned":true})},
     *     inverseJoinColumns={@ORM\JoinColumn(name="ID_FILS_ETABLISSEMENT", referencedColumnName="ID_ETABLISSEMENT", options={"unsigned":true})}
     * )
     *
     * @ORM\ManyToMany(targetEntity=Etablissement::class, inversedBy="parents")
     */
    private $fils;

    /**
     * @var Collection<int, Etablissement>
     *
     * @ORM\JoinTable(
     *     name="etablissementlie",
     *     joinColumns={@ORM\JoinColumn(name="ID_FILS_ETABLISSEMENT", referencedColumnName="ID_ETABLISSEMENT", options={"unsigned":true})},
     *     inverseJoinColumns={@ORM\JoinColumn(name="ID_ETABLISSEMENT", referencedColumnName="ID_ETABLISSEMENT", options={"unsigned":true})}
     * )
     *
     * @ORM\ManyToMany(targetEntity=Etablissement::class, mappedBy="fils")
     */
    private $parents;

    /**
     * @var ?Dossier
     *
     * @ORM\OneToOne(targetEntity=Dossier::class, cascade={"persist", "remove"})
     *
     * @ORM\JoinColumn(name="ID_DOSSIER_DONNANT_AVIS", referencedColumnName="ID_DOSSIER", nullable=true)
     */
    private $dossierDonnantAvis;

    /**
     * @var ?string
     *
     * @ORM\Column(name="DESCTECH_RISQUES_NATURELS_ETABLISSEMENT", type="text", nullable=true)
     */
    private $risquesNaturels;

    /**
     * @var ?string
     *
     * @ORM\Column(name="DESCTECH_RISQUES_TECHNOLOGIQUES_ETABLISSEMENT", type="text", nullable=true)
     */
    private $risquesTechnologiques;

    public function __construct()
    {
        $this->dossiers = new ArrayCollection();
        $this->adresses = new ArrayCollection();
        $this->fils = new ArrayCollection();
        $this->parents = new ArrayCollection();
    }

    public function getId(): int
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

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(?string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getFils(): Collection
    {
        return $this->fils;
    }

    public function addFils(self $fils): self
    {
        if (!$this->fils->contains($fils)) {
            $this->fils[] = $fils;
        }

        return $this;
    }

    public function removeFils(self $fils): self
    {
        $this->fils->removeElement($fils);

        return $this;
    }

    /**
     * Le schéma de base de données exige un ManyToMany alors qu'on ne peut avoir qu'un seul parent.
     * Cette méthode renvoie donc le 1er élément de la Collection ou null s'il n'y a pas de parent.
     */
    public function getParent(): ?Etablissement
    {
        return $this->parents->first() ?: null;
    }

    /**
     * @return Collection<int, self>
     */
    public function getParents(): Collection
    {
        return $this->parents;
    }

    public function addParent(self $parent): self
    {
        if (!$this->parents->contains($parent)) {
            $this->parents[] = $parent;
            $parent->addFils($this);
        }

        return $this;
    }

    public function removeParent(self $parent): self
    {
        if ($this->parents->removeElement($parent)) {
            $parent->removeFils($this);
        }

        return $this;
    }

    public function getDossierDonnantAvis(): ?Dossier
    {
        return $this->dossierDonnantAvis;
    }

    public function setDossierDonnantAvis(?Dossier $dossierDonnantAvis): self
    {
        $this->dossierDonnantAvis = $dossierDonnantAvis;

        return $this;
    }

    public function hasAvisDerogations(): bool
    {
        $dossiers = $this->getDossiers();

        foreach ($dossiers as $dossier) {
            $avisDerogations = $dossier->getAvisDerogations();

            foreach ($avisDerogations as $avisDerogation) {
                if ($avisDerogation->getDisplayHistorique()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getRisquesNaturels(): ?string
    {
        return $this->risquesNaturels;
    }

    public function setRisquesNaturels(?string $risquesNaturels): self
    {
        $this->risquesNaturels = $risquesNaturels;

        return $this;
    }

    public function getRisquesTechnologiques(): ?string
    {
        return $this->risquesTechnologiques;
    }

    public function setRisquesTechnologiques(?string $risquesTechnologiques): self
    {
        $this->risquesTechnologiques = $risquesTechnologiques;

        return $this;
    }

    public function hasAvisDiffere(): bool
    {
        $dossiersAvisDiffere = $this->getDossiers()->filter(function (Dossier $dossier): bool {
            return $dossier->getAvisDiffere()
                && null === $dossier->getDateSuppression();
        });

        return !$dossiersAvisDiffere->isEmpty();
    }

    public function hasEcheancierTravaux(): bool
    {
        $dossiersEcheancier = $this->getDossiers()->filter(function (Dossier $dossier): bool {
            $nature = $dossier->getNature();

            if (null === $nature) {
                return false;
            }

            return DossierNatureListe::NATURE_ECHEANCIER_TRAVAUX === $nature->getId()
                && null === $dossier->getDateSuppression();
        });

        return !$dossiersEcheancier->isEmpty();
    }
}
