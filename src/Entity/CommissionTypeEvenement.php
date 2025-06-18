<?php

namespace App\Entity;

use App\Repository\CommissionTypeEvenementRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CommissionTypeEvenementRepository::class)
 * 
 * @ORM\Table(name="commissiontypeevenement")
 */
class CommissionTypeEvenement
{
    /**
     * @var int
     * 
     * @ORM\Id
     * 
     * @ORM\GeneratedValue
     * 
     * @ORM\Column(name="ID_COMMISSIONTYPEEVENEMENT", type="integer", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     * 
     * @ORM\Column(name="LIBELLE_COMMISSIONTYPEEVENEMENT", type="string", length=255)
     */
    private $libelle;

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
}
