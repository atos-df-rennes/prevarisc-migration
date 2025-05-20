<?php

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EtablissementInformationsPreventionnisteRepository::class)
 * 
 * @ORM\Table(name="etablissementinformationspreventionniste")
*/
class EtablissementInformationsPreventionniste
{
   /**
    * @var EtablissementInformations
    *
    * @ORM\Id
    * 
    * @ORM\ManyToOne(targetEntity=EtablissementInformations::class, inversedBy="preventionnistes")
    * 
    * @ORM\JoinColumn(name="ID_ETABLISSEMENTINFORMATIONS", referencedColumnName="ID_ETABLISSEMENTINFORMATIONS", nullable=false, onDelete="CASCADE")
    */
    private $idEtablissementInformation;

   /**
    * @var Utilisateur
    * 
    * @ORM\Id
    *
    * @ORM\ManyToOne(targetEntity=Utilisateur::class)
    *
    * @ORM\JoinColumn(name="ID_UTILISATEUR", referencedColumnName="ID_UTILISATEUR", nullable=false, onDelete="CASCADE")
    */
    private $idUtilisateur;

    public function getIdEtablissementInformation(): EtablissementInformations
    {
        return $this->idEtablissementInformation;
    }

    public function setIdEtablissementInformation(EtablissementInformations $idEtablissementInformation): self
    {
        $this->idEtablissementInformation = $idEtablissementInformation;
        return $this;
    }

    public function getIdUtilisateur(): Utilisateur
    {
        return $this->idUtilisateur;
    }

    public function setIdUtilisateur(Utilisateur $idUtilisateur): self
    {
        $this->idUtilisateur = $idUtilisateur;
        return $this;
    }
    
}
