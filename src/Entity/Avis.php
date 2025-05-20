<?php

namespace App\Entity;

use App\Repository\AvisRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AvisRepository::class)
 * 
 * @ORM\Table(name="avis")
 */
class Avis
{
    /**
     * @var int
     * 
     * @ORM\Id
     * 
     * @ORM\GeneratedValue
     * 
     * @ORM\Column(name="ID_AVIS", type="integer")
     */
    private $id;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="LIBELLE_AVIS", type="string", length=30, nullable=true)
     */
    private $libelleAvis;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelleAvis(): ?string
    {
        return $this->libelleAvis;
    }

    public function setLibelleAvis(?string $libelleAvis): self
    {
        $this->libelleAvis = $libelleAvis;

        return $this;
    }
}
