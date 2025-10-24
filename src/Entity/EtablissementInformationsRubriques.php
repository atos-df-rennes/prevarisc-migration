<?php

namespace App\Entity;

use App\Repository\EtablissementInformationsRubriqueRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EtablissementInformationsRubriqueRepository::class)
 *
 * @ORM\Table(name="etablissementinformationsrubrique")
 */
class EtablissementInformationsRubriques
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="ID_ETABLISSEMENTINFORMATIONSRUBRIQUE", type="bigint")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $numero;

    /**
     * @ORM\Column(type="float")
     */
    private $valeur;

    /**
     * @ORM\Column(type="string", length=500)
     */
    private $nom;

    /**
     * @ORM\Column(type="smallint")
     */
    private $rubrique;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $classement;

    /**
     * @var ?EtablissementInformations
     *
     * @ORM\ManyToOne(targetEntity=EtablissementInformations::class, inversedBy="rubriquesIcpe")
     *
     * @ORM\JoinColumn(name="ID_ETABLISSEMENTINFORMATIONS", referencedColumnName="ID_ETABLISSEMENTINFORMATIONS", nullable=false, options={"unsigned":true})
     */
    private $etablissementInformations;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getValeur(): ?float
    {
        return $this->valeur;
    }

    public function setValeur(float $valeur): self
    {
        $this->valeur = $valeur;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getRubrique(): ?int
    {
        return $this->rubrique;
    }

    public function setRubrique(int $rubrique): self
    {
        $this->rubrique = $rubrique;

        return $this;
    }

    public function getClassement(): ?string
    {
        return $this->classement;
    }

    public function setClassement(string $classement): self
    {
        $this->classement = $classement;

        return $this;
    }

    public function getEtablissementInformations(): ?EtablissementInformations
    {
        return $this->etablissementInformations;
    }

    public function setEtablissementInformations(?EtablissementInformations $etablissementInformations): self
    {
        $this->etablissementInformations = $etablissementInformations;

        return $this;
    }

}
