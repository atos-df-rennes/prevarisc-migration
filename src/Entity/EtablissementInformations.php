<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity(repositoryClass=EtablissementInformationsRepository::class)
 *
 * @ORM\Table(name="etablissementinformations")
 */
class EtablissementInformations
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_ETABLISSEMENTINFORMATIONS", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_ETABLISSEMENTINFORMATIONS", type="string", length=255)
     */
    private $libelle;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="DATE_ETABLISSEMENTINFORMATIONS", type="date")
     */
    private $date;

    /**
     * @var ?Etablissement
     *
     * @ORM\ManyToOne(targetEntity=Etablissement::class, inversedBy="informations")
     *
     * @ORM\JoinColumn(name="ID_ETABLISSEMENT", referencedColumnName="ID_ETABLISSEMENT", nullable=false, options={"unsigned":true})
     */
    private $etablissement;

    /**
     * @var bool
     * 
     * @ORM\Column(name="ICPE_ETABLISSEMENTINFORMATIONS", type="boolean", nullable=true)
     */
    private $codeICPE;

    /**
     * @var bool
     * 
     * @ORM\Column(name="PERIODICITE_ETABLISSEMENTINFORMATIONS", type="boolean", nullable=true)
     */
    private $periodicite;

    /**
     * @var bool
     * 
     * @ORM\Column(name="DROITPUBLIC_ETABLISSEMENTINFORMATIONS", type="boolean", nullable=true)
     */
    private $droitPublic;

    /**
     * @var bool
     * 
     * @ORM\Column(name="LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS", type="boolean", nullable=true)
     */
    private $localSommeil;

    /**
     * @var int
     * 
     * @ORM\Column(name="EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS", type="integer", nullable=true)
     */
    private $effectifPublic;

    /**
     * @var int
     * 
     * @ORM\Column(name="EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS", type="integer", nullable=true)
     */
    private $effectifPersonnel;

    /**
     * @var ?Genre
     * 
     * @ORM\ManyToOne(targetEntity="Genre")
     * 
     * @ORM\JoinColumn(name="ID_GENRE", referencedColumnName="ID_GENRE", nullable=false)
     */
    private $idGenre;

    /**
     * @var Collection<int, EtablissementInformationsPreventionniste>
     * 
     * @ORM\OneToMany(targetEntity=EtablissementInformationsPreventionniste::class, mappedBy="idEtablissementInformation", cascade={"persist"}, orphanRemoval=true)
     * 
     */
    private $preventionnistes;

    public function __construct()
    {
        $this->preventionnistes = new ArrayCollection();
    }
     

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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

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

    public function getCodeICPE(): ?bool
    {
        return $this->codeICPE;
    }

    public function setCodeICPE(?bool $codeICPE): self
    {
        $this->codeICPE = $codeICPE;

        return $this;
    }

    public function getPeriodicite(): ?bool
    {
        return $this->periodicite;
    }

    public function setPeriodicite(?bool $periodicite): self
    {
        $this->periodicite = $periodicite;

        return $this;
    }

    public function getDroitPublic(): ?bool
    {
        return $this->droitPublic;
    }

    public function setDroitPublic(?bool $droitPublic): self
    {
        $this->droitPublic = $droitPublic;

        return $this;
    }

    public function getLocalSommeil(): ?bool
    {
        return $this->localSommeil;
    }

    public function setLocalSommeil(?bool $localSommeil): self
    {
        $this->localSommeil = $localSommeil;

        return $this;
    }

    public function getEffectifPublic(): ?int
    {
        return $this->effectifPublic;
    }

    public function setEffectifPublic(?int $effectifPublic): self
    {
        $this->effectifPublic = $effectifPublic;

        return $this;
    }

    public function getEffectifPersonnel(): ?int
    {
        return $this->effectifPersonnel;
    }

    public function setEffectifPersonnel(?int $effectifPersonnel): self
    {
        $this->effectifPersonnel = $effectifPersonnel;

        return $this;
    }

    public function getIdGenre(): ?Genre
    {
        return $this->idGenre;
    }

    public function setIdGenre(Genre $idGenre): self
    {
        $this->idGenre = $idGenre;

        return $this;
    }
    public function getPreventionnistes(): Collection
    {
        return $this->preventionnistes;
    }
}
