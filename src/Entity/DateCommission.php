<?php

namespace App\Entity;

use App\Repository\DateCommissionRepository;
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
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_DATECOMMISSION", type="bigint")
     */
    private $id;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="DATE_COMMISSION", type="date", nullable=false)
     */
    private $date;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="HEUREDEB_COMMISSION", type="time", nullable=false, options={"default":"09:00:00"})
     */
    private $heureDebut;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="HEUREFIN_COMMISSION", type="time", nullable=false, options={"default":"18:00:00"})
     */
    private $heureFin;

    /**
     * @var ?string
     *
     * @ORM\Column(name="LIBELLE_DATECOMMISSION", type="string", length=255, nullable=true)
     */
    private $libelle;

    /**
     * @var bool
     *
     * @ORM\Column(name="GESTION_HEURES", type="boolean", nullable=false)
     */
    private $gestionHeures;

    /**
     * @var ?int
     *
     * @ORM\Column(name="DATECOMMISSION_LIEES", type="bigint", nullable=true)
     */
    private $dateCommissionLiees;

    /**
     * @var CommissionTypeEvenement
     *
     * @ORM\ManyToOne(targetEntity=CommissionTypeEvenement::class)
     *
     * @ORM\JoinColumn(name="ID_COMMISSIONTYPEEVENEMENT", referencedColumnName="ID_COMMISSIONTYPEEVENEMENT", nullable=false, options={"unsigned":true})
     */
    private $typeEvenement;

    /**
     * @var ?Commission
     *
     * @ORM\ManyToOne(targetEntity=Commission::class, inversedBy="dateCommissions")
     *
     * @ORM\JoinColumn(name="COMMISSION_CONCERNE", referencedColumnName="ID_COMMISSION", nullable=false, options={"unsigned":true})
     */
    private $commission;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(\DateTimeInterface $heureDebut): self
    {
        $this->heureDebut = $heureDebut;

        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heureFin;
    }

    public function setHeureFin(\DateTimeInterface $heureFin): self
    {
        $this->heureFin = $heureFin;

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

    public function getTypeEvenement(): ?CommissionTypeEvenement
    {
        return $this->typeEvenement;
    }

    public function setTypeEvenement(CommissionTypeEvenement $typeEvenement): self
    {
        $this->typeEvenement = $typeEvenement;

        return $this;
    }

    public function getCommission(): ?Commission
    {
        return $this->commission;
    }

    public function setCommission(?Commission $commission): self
    {
        $this->commission = $commission;

        return $this;
    }
}
