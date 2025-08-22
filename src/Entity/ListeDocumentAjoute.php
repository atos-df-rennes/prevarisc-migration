<?php

namespace App\Entity;

use App\Repository\ListeDocumentAjouteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ListeDocumentAjouteRepository::class)
 *
 * @ORM\Table(name="listedocajout")
 */
class ListeDocumentAjoute
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_DOCAJOUT", type="bigint")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_DOCAJOUT", type="text", nullable=false)
     */
    private $libelle;

    /**
     * @var ?string
     *
     * @ORM\Column(name="REF_DOCAJOUT", type="string", length=255, nullable=true)
     */
    private $reference;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATE_DOCAJOUT", type="date", nullable=true)
     */
    private $date;

    /**
     * @var DossierNatureListe
     *
     * @ORM\ManyToOne(targetEntity=DossierNatureListe::class)
     *
     * @ORM\JoinColumn(name="ID_NATURE", referencedColumnName="ID_DOSSIERNATURE", nullable=false)
     */
    private $nature;

    /**
     * @var ?Dossier
     *
     * @ORM\ManyToOne(targetEntity=Dossier::class, inversedBy="documentsAjoutes")
     *
     * @ORM\JoinColumn(name="ID_DOSSIER", referencedColumnName="ID_DOSSIER", nullable=false)
     */
    private $dossier;

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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getNature(): ?DossierNatureListe
    {
        return $this->nature;
    }

    public function setNature(DossierNatureListe $nature): self
    {
        $this->nature = $nature;

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
