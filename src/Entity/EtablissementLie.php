<?php

namespace App\Entity;

use App\Repository\EtablissementLieRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EtablissementLieRepository::class)
 * 
 * @ORM\Table(name="etablissementlie")
 */
class EtablissementLie
{
    /**
     * @var Etablissement
     * 
     * @ORM\Id
     * 
     * @ORM\ManyToOne(targetEntity=Etablissement::class)
     * 
     * @ORM\JoinColumn(name="ID_ETABLISSEMENT", referencedColumnName="ID_ETABLISSEMENT", nullable=false, onDelete="CASCADE")
     */
    private $etablissement;

    /**
     * @var Etablissement
     * 
     * @ORM\Id
     * 
     * @ORM\ManyToOne(targetEntity=Etablissement::class)
     * 
     * @ORM\JoinColumn(name="ID_FILS_ETABLISSEMENT", referencedColumnName="ID_ETABLISSEMENT", nullable=false, onDelete="CASCADE")
     */
    private $filsEtablissement;

   public function getEtablissement(): ?Etablissement
    {
        return $this->etablissement;
    }

    public function setEtablissement(?Etablissement $etablissement): self
    {
        $this->etablissement = $etablissement;
        
        return $this;
    }

     public function getFilsEtablissement(): ?Etablissement
    {
        return $this->filsEtablissement;
    }

    public function setFilsEtablissement(?Etablissement $filsEtablissement): self
    {
        $this->filsEtablissement = $filsEtablissement;

        return $this;
    }
}
