<?php

namespace App\Entity;

use App\Repository\EtablissementAdresseRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EtablissementAdresseRepository::class)
 *
 * @ORM\Table(name="etablissementadresse")
 */
class EtablissementAdresse
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_ADRESSE", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var ?string
     *
     * @ORM\Column(name="NUMERO_ADRESSE", type="string", length=255, nullable=true)
     */
    private $numero;

    /**
     * @var ?string
     *
     * @ORM\Column(name="COMPLEMENT_ADRESSE", type="string", length=255, nullable=true)
     */
    private $complement;

    /**
     * @var ?float
     *
     * @ORM\Column(name="LON_ETABLISSEMENTADRESSE", type="float", nullable=true)
     */
    private $longitude;

    /**
     * @var ?float
     *
     * @ORM\Column(name="LAT_ETABLISSEMENTADRESSE", type="float", nullable=true)
     */
    private $latitude;

    /**
     * @var Commune
     *
     * @ORM\ManyToOne(targetEntity=Commune::class)
     *
     * @ORM\JoinColumn(name="NUMINSEE_COMMUNE", referencedColumnName="NUMINSEE_COMMUNE", nullable=false, options={"fixed":true})
     */
    private $commune;

    /**
     * @var ?Etablissement
     *
     * @ORM\ManyToOne(targetEntity=Etablissement::class, inversedBy="adresses")
     *
     * @ORM\JoinColumn(name="ID_ETABLISSEMENT", referencedColumnName="ID_ETABLISSEMENT", nullable=false, options={"unsigned":true})
     */
    private $etablissement;

    /**
     * @var ?AdresseRue
     *
     * @ORM\ManyToOne(targetEntity=AdresseRue::class)
     *
     * @ORM\JoinColumn(name="ID_RUE", referencedColumnName="ID_RUE", nullable=true, options={"unsigned":true})
     */
    private $rue;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(?string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getComplement(): ?string
    {
        return $this->complement;
    }

    public function setComplement(?string $complement): self
    {
        $this->complement = $complement;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getCommune(): ?Commune
    {
        return $this->commune;
    }

    public function setCommune(Commune $commune): self
    {
        $this->commune = $commune;

        return $this;
    }

    public function getEtablissement(): ?Etablissement
    {
        return $this->etablissement;
    }

    public function setEtablissement(?Etablissement $etablissement): self
    {
        $this->etablissement = $etablissement;

        return $this;
    }

    public function getRue(): ?AdresseRue
    {
        return $this->rue;
    }

    public function setRue(?AdresseRue $rue): self
    {
        $this->rue = $rue;

        return $this;
    }
}
