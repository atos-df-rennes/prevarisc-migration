<?php

namespace App\Entity;

use App\Repository\EtablissementInformationsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EtablissementInformationsRepository::class)
 * @ORM\Table(name="etablissementinformations")
 */
class EtablissementInformations
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
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
     * @var Etablissement
     *
     * @ORM\ManyToOne(targetEntity=Etablissement::class, inversedBy="informations")
     * @ORM\JoinColumn(name="ID_ETABLISSEMENT", referencedColumnName="ID_ETABLISSEMENT", nullable=false, options={"unsigned":true})
     */
    private $etablissement;

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
}
