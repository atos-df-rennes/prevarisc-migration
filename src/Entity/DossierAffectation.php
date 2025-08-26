<?php

namespace App\Entity;

use App\Repository\DossierAffectationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DossierAffectationRepository::class)
 *
 * @ORM\Table(name="dossieraffectation")
 */
class DossierAffectation
{
    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="HEURE_DEB_AFFECT", type="time", nullable=true)
     */
    private $heureDebut;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="HEURE_FIN_AFFECT", type="time", nullable=true)
     */
    private $heureFin;

    /**
     * @var int
     *
     * @ORM\Column(name="NUM_DOSSIER", type="integer", nullable=false)
     */
    private $numero;

    /**
     * @var ?DateCommission
     *
     * @ORM\Id
     *
     * @ORM\ManyToOne(targetEntity=DateCommission::class, inversedBy="dossierAffectations")
     *
     * @ORM\JoinColumn(name="ID_DATECOMMISSION_AFFECT", referencedColumnName="ID_DATECOMMISSION", nullable=false)
     */
    private $dateCommission;

    /**
     * @var Dossier
     *
     * @ORM\Id
     *
     * @ORM\ManyToOne(targetEntity=Dossier::class)
     *
     * @ORM\JoinColumn(name="ID_DOSSIER_AFFECT", referencedColumnName="ID_DOSSIER", nullable=false)
     */
    private $dossier;

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(?\DateTimeInterface $heureDebut): self
    {
        $this->heureDebut = $heureDebut;

        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heureFin;
    }

    public function setHeureFin(?\DateTimeInterface $heureFin): self
    {
        $this->heureFin = $heureFin;

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

    public function getDateCommission(): ?DateCommission
    {
        return $this->dateCommission;
    }

    public function setDateCommission(?DateCommission $dateCommission): self
    {
        $this->dateCommission = $dateCommission;

        return $this;
    }

    public function getDossier(): ?Dossier
    {
        return $this->dossier;
    }

    public function setDossier(Dossier $dossier): self
    {
        $this->dossier = $dossier;

        return $this;
    }
}
