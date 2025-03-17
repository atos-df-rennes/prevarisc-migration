<?php

namespace App\Entity;

use App\Repository\EtablissementRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EtablissementRepository::class)
 * @ORM\Table(indexes={@ORM\Index(columns={"DATESUPPRESSION_ETABLISSEMENT"})})
 */
class Etablissement
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="ID_ETABLISSEMENT", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATESUPPRESSION_ETABLISSEMENT", type="date", nullable=true)
     */
    private $dateSuppression;

    /**
     * @var ?Utilisateur
     *
     * @ORM\ManyToOne(targetEntity=Utilisateur::class)
     *
     * @ORM\JoinColumn(name="DELETED_BY", referencedColumnName="ID_UTILISATEUR", options={"unsigned":true})
     */
    private $deletedBy;

    /**
     * @var ?EtablissementAdresse
     *
     * @ORM\OneToOne(targetEntity=EtablissementAdresse::class, mappedBy="etablissement")
     */
    private $adresse;

    /**
     * @var Collection<int, EtablissementInformations>
     *
     * @ORM\OneToMany(targetEntity=EtablissementInformations::class, mappedBy="etablissement")
     */
    private $informations;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateSuppression(): ?\DateTimeInterface
    {
        return $this->dateSuppression;
    }

    public function setDateSuppression(?\DateTimeInterface $dateSuppression): self
    {
        $this->dateSuppression = $dateSuppression;

        return $this;
    }

    public function getDeletedBy(): ?Utilisateur
    {
        return $this->deletedBy;
    }

    public function setDeletedBy(?Utilisateur $deletedBy): self
    {
        $this->deletedBy = $deletedBy;

        return $this;
    }

    /**
     * @return Collection<int, EtablissementInformations>
     */
    public function getInformations(): Collection
    {
        return $this->informations;
    }

    public function addInformations(EtablissementInformations $informations): self
    {
        if (!$this->informations->contains($informations)) {
            $this->informations[] = $informations;
            $informations->setEtablissement($this);
        }

        return $this;
    }

    public function removeInformations(EtablissementInformations $informations): self
    {
        // set the owning side to null (unless already changed)
        if ($this->informations->removeElement($informations) && $informations->getEtablissement() === $this) {
            $informations->setEtablissement(null);
        }

        return $this;
    }

    public function getAdresse(): ?EtablissementAdresse
    {
        return $this->adresse;
    }

    public function setAdresse(?EtablissementAdresse $adresse): self
    {
        $this->adresse = $adresse;

        return $this;
    }
}
