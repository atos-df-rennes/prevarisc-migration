<?php

namespace App\Entity;

use App\Repository\DossierAffectationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DossierAffectationRepository::class)
 * @ORM\Table(name="dossieraffectation")
 */
class DossierAffectation
{
    /**
     * @var int
     * 
     * @ORM\Id
     * 
     * @ORM\GeneratedValue
     * 
     * @ORM\Column( type="integer")
     */
    private $id;

    /**
     * @var ?\DateTimeInterface
     * 
     * @ORM\Column(name="HEURE_DEB_AFFECT", type="time", nullable=true)
     */
    private $heureDebAffect;

    /**
     * @var ?\DateTimeInterface
     * 
     * @ORM\Column(name="HEURE_FIN_AFFECT", type="time", nullable=true)
     */
    private $heureFinAffect;

    /**
     * @var int
     * 
     * @ORM\Column(name="NUM_DOSSiER", type="integer")
     */
    private $numDossier;

    /**
     * @var ?Dossier
     * 
     * @ORM\ManyToOne(targetEntity=Dossier::class)
     * 
     * @ORM\JoinColumn(name="ID_DOSSIER_AFFECT", referencedColumnName= "ID_DOSSIER", nullable=false)
     */
    private $idDossierAffect;

    /**
     * @var ?DateCommission
     * 
     * @ORM\ManyToOne(targetEntity=DateCommission::class)
     * 
     * @ORM\JoinColumn(name="ID_DATECOMMISSION_AFFECT", referencedColumnName= "ID_DATECOMMISSION", nullable=false)
     */
    private $idDateCommissionAffect;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHeureDebAffect(): ?\DateTimeInterface
    {
        return $this->heureDebAffect;
    }

    public function setHeureDebAffect(?\DateTimeInterface $heureDebAffect): self
    {
        $this->heureDebAffect = $heureDebAffect;

        return $this;
    }

    public function getHeureFinAffect(): ?\DateTimeInterface
    {
        return $this->heureFinAffect;
    }

    public function setHeureFinAffect(?\DateTimeInterface $heureFinAffect): self
    {
        $this->heureFinAffect = $heureFinAffect;

        return $this;
    }

    public function getNumDossier(): ?int
    {
        return $this->numDossier;
    }

    public function setNumDossier(int $numDossier): self
    {
        $this->numDossier = $numDossier;

        return $this;
    }

    public function getIdDossierAffect(): ?Dossier
    {
        return $this->idDossierAffect;
    }

    public function setIdDossierAffect(?Dossier $idDossierAffect): self
    {
        $this->idDossierAffect = $idDossierAffect;

        return $this;
    }

    public function getIdDateCommissionAffect(): ?DateCommission
    {
        return $this->idDateCommissionAffect;
    }

    public function setIdDateCommissionAffect(?DateCommission $idDateCommissionAffect): self
    {
        $this->idDateCommissionAffect = $idDateCommissionAffect;

        return $this;
    }
}
