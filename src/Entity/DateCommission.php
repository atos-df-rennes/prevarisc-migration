<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\DateCommissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DateCommissionRepository::class)]
#[ORM\Table(name: 'datecommission')]
class DateCommission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_DATECOMMISSION', type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(name: 'DATE_COMMISSION', type: Types::DATE_MUTABLE, nullable: false)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(name: 'HEUREDEB_COMMISSION', type: Types::TIME_MUTABLE, nullable: false, options: ['default' => '09:00:00'])]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(name: 'HEUREFIN_COMMISSION', type: Types::TIME_MUTABLE, nullable: false, options: ['default' => '18:00:00'])]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\Column(name: 'LIBELLE_DATECOMMISSION', type: Types::STRING, length: 255, nullable: true)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'GESTION_HEURES', type: Types::BOOLEAN, nullable: false)]
    private ?bool $gestionHeures = null;

    #[ORM\Column(name: 'DATECOMMISSION_LIEES', type: Types::BIGINT, nullable: true)]
    private ?int $dateCommissionLiees = null;

    #[ORM\ManyToOne(targetEntity: CommissionTypeEvenement::class)]
    #[ORM\JoinColumn(name: 'ID_COMMISSIONTYPEEVENEMENT', referencedColumnName: 'ID_COMMISSIONTYPEEVENEMENT', nullable: false, options: ['unsigned' => true])]
    private ?CommissionTypeEvenement $typeEvenement = null;

    #[ORM\ManyToOne(targetEntity: Commission::class, inversedBy: 'dateCommissions')]
    #[ORM\JoinColumn(name: 'COMMISSION_CONCERNE', referencedColumnName: 'ID_COMMISSION', nullable: false, options: ['unsigned' => true])]
    private ?Commission $commission = null;

    /**
     * @var Collection<int, PieceJointe>
     */
    #[ORM\JoinTable(name: 'datecommissionpj')]
    #[ORM\JoinColumn(name: 'ID_DATECOMMISSION', referencedColumnName: 'ID_DATECOMMISSION')]
    #[ORM\InverseJoinColumn(name: 'ID_PIECEJOINTE', referencedColumnName: 'ID_PIECEJOINTE')]
    #[ORM\ManyToMany(targetEntity: PieceJointe::class)]
    private Collection $piecesJointes;

    /**
     * @var Collection<int, DossierAffectation>
     */
    #[ORM\OneToMany(targetEntity: DossierAffectation::class, mappedBy: 'dateCommission', orphanRemoval: true)]
    private Collection $dossierAffectations;

    public function __construct()
    {
        $this->piecesJointes = new ArrayCollection();
        $this->dossierAffectations = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, PieceJointe>
     */
    public function getPiecesJointes(): Collection
    {
        return $this->piecesJointes;
    }

    public function addPiecesJointe(PieceJointe $piecesJointe): self
    {
        if (!$this->piecesJointes->contains($piecesJointe)) {
            $this->piecesJointes[] = $piecesJointe;
        }

        return $this;
    }

    public function removePiecesJointe(PieceJointe $piecesJointe): self
    {
        $this->piecesJointes->removeElement($piecesJointe);

        return $this;
    }

    /**
     * @return Collection<int, DossierAffectation>
     */
    public function getDossierAffectations(): Collection
    {
        return $this->dossierAffectations;
    }

    public function addDossierAffectation(DossierAffectation $dossierAffectation): self
    {
        if (!$this->dossierAffectations->contains($dossierAffectation)) {
            $this->dossierAffectations[] = $dossierAffectation;
            $dossierAffectation->setDateCommission($this);
        }

        return $this;
    }

    public function removeDossierAffectation(DossierAffectation $dossierAffectation): self
    {
        // set the owning side to null (unless already changed)
        if ($this->dossierAffectations->removeElement($dossierAffectation) && $dossierAffectation->getDateCommission() === $this) {
            $dossierAffectation->setDateCommission(null);
        }

        return $this;
    }
}
