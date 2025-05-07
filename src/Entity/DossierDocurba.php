<?php

namespace App\Entity;

use App\Repository\DossierDocurbaRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DossierDocurbaRepository::class)
 * 
 * @ORM\Table(name="dossierdocurba")
 */
class DossierDocurba
{
    /**
     * @var int
     * 
     * @ORM\Id
     * 
     * @ORM\GeneratedValue
     * 
     * @ORM\Column(name="ID_DOCURBA", type="integer")
     */
    private $id;

    /**
     * @var string
     * 
     * @ORM\Column(name="NUM_DOCURBA", type="string", length=100)
     */
    private $numDocurba;


    /**
     * @var Dossier
     * 
     * @ORM\ManyToOne(targetEntity=Dossier::class, inversedBy="numDossierDocurba")
     * 
     * @ORM\JoinColumn(name="ID_DOSSIER", referencedColumnName="ID_DOSSIER", nullable=false)
     */
    private $dossier;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumDocurba(): ?string
    {
        return $this->numDocurba;
    }

    public function setNumDocurba(string $numDocurba): self
    {
        $this->numDocurba = $numDocurba;

        return $this;
    }

    public function getDossier(): ?Dossier
    {
        return $this->dossier;
    }

    public function setDossier(?Dossier $dossier): self
    {
        $this->dossier = $dossier;

        return $this;
    }

}
