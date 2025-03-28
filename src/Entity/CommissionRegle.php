<?php

namespace App\Entity;

use App\Repository\CommissionRegleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CommissionRegleRepository::class)
 * @ORM\Table(name="commissionregle")
 */
class CommissionRegle
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="ID_REGLE", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var ?Groupement
     *
     * @ORM\ManyToOne(targetEntity=Groupement::class)
     *
     * @ORM\JoinColumn(name="ID_GROUPEMENT", referencedColumnName="ID_GROUPEMENT")
     */
    private $groupement;

    /**
     * @var Commission
     *
     * @ORM\ManyToOne(targetEntity=Commission::class, inversedBy="regles")
     *
     * @ORM\JoinColumn(name="ID_COMMISSION", referencedColumnName="ID_COMMISSION", nullable=false, options={"unsigned":true}, onDelete="CASCADE")
     */
    private $commission;

    /**
     * @var ?Commune
     *
     * @ORM\ManyToOne(targetEntity=Commune::class)
     *
     * @ORM\JoinColumn(name="NUMINSEE_COMMUNE", referencedColumnName="NUMINSEE_COMMUNE", onDelete="CASCADE")
     */
    private $commune;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroupement(): ?Groupement
    {
        return $this->groupement;
    }

    public function setGroupement(?Groupement $groupement): self
    {
        $this->groupement = $groupement;

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

    public function getCommune(): ?Commune
    {
        return $this->commune;
    }

    public function setCommune(?Commune $commune): self
    {
        $this->commune = $commune;

        return $this;
    }
}
