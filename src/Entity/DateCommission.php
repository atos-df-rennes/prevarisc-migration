<?php

namespace App\Entity;

use App\Repository\DateCommissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DateCommissionRepository::class)
 * 
 * @ORM\Table(name="datecommission")
 */
class DateCommission
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="ID_DATECOMMISSION", type="bigint")
     */
    private $id;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="DATE_COMMISSION", type="date")
     */
    private $dateCommission;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name= "HEUREDEB_COMMISSION", type="time")
     */
    private $heureDebutCommission;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name= "HEUREFIN_COMMISSION", type="time")
     */
    private $heureFinCommission;

    /**
     * @var ?string
     * 
     * @ORM\Column(name= "LIBELLE_DATECOMMISSION", type="string", length=255, nullable=true)
     */
    private $libelleDateCommission;

    /**
     * @var ?bool
     * 
     * @ORM\Column(name= "GESTION_HEURES", type="boolean")
     */
    private $gestionHeures;

    /**
     * @var ?bool
     * 
     * @ORM\Column(name= "DATECOMMISSION_LIEES", type="bigint", nullable=true)
     */
    private $dateCommissionLiees;

    /**
     * @var ?Commission
     * 
     * @ORM\ManyToOne(targetEntity=Commission::class, inversedBy="dateCommissions")
     * @ORM\JoinColumn(name= "COMMISSION_CONCERNE",  referencedColumnName= "ID_COMMISSION", nullable=false)
     */
    private $commissionConcerne;


    /**
     * @ORM\OneToMany(targetEntity=DossierAffectation::class, mappedBy="idDateCommissionAffect")
     */
    private $dossierAffectations;

    public function __construct()
    {
        $this->dossierAffectations = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateCommission(): ?\DateTimeInterface
    {
        return $this->dateCommission;
    }

    public function setDateCommission(\DateTimeInterface $dateCommission): self
    {
        $this->dateCommission = $dateCommission;

        return $this;
    }

    public function getHeureDebutCommission(): ?\DateTimeInterface
    {
        return $this->heureDebutCommission;
    }

    public function setHeureDebutCommission(\DateTimeInterface $heureDebutCommission): self
    {
        $this->heureDebutCommission = $heureDebutCommission;

        return $this;
    }

    public function getHeureFinCommission(): ?\DateTimeInterface
    {
        return $this->heureFinCommission;
    }

    public function setHeureFinCommission(\DateTimeInterface $heureFinCommission): self
    {
        $this->heureFinCommission = $heureFinCommission;

        return $this;
    }

    public function getLibelleDateCommission(): ?string
    {
        return $this->libelleDateCommission;
    }

    public function setLibelleDateCommission(?string $libelleDateCommission): self
    {
        $this->libelleDateCommission = $libelleDateCommission;

        return $this;
    }

    public function getGestionHeures(): ?bool
    {
        return $this->gestionHeures;
    }

    public function setGestionHeures(bool $gestionHeures): self
    {
        $this->gestionHeures = $gestionHeures;

        return $this;
    }

    public function getDateCommissionLiees(): ?int
    {
        return $this->dateCommissionLiees;
    }

    public function setDateCommissionLiees(?int $dateCommissionLiees): self
    {
        $this->dateCommissionLiees = $dateCommissionLiees;

        return $this;
    }

    public function getCommissionConcerne(): ?Commission
    {
        return $this->commissionConcerne;
    }

    public function setCommissionConcerne(?Commission $commissionConcerne): self
    {
        $this->commissionConcerne = $commissionConcerne;

        return $this;
    }

    public function getDossierAffectations(): Collection
{
    return $this->dossierAffectations;
}
}
