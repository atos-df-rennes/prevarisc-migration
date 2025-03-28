<?php

namespace App\Entity;

use App\Repository\CommissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CommissionRepository::class)
 */
class Commission
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_COMMISSION", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_COMMISSION", type="string", length=80)
     */
    private $libelle;

    /**
     * @var ?string
     *
     * @ORM\Column(name="DOCUMENT_CR", type="string", length=255, nullable=true)
     */
    private $documentCr;

    /**
     * @var ?CommissionType
     *
     * @ORM\ManyToOne(targetEntity=CommissionType::class, inversedBy="commissions")
     *
     * @ORM\JoinColumn(name="ID_COMMISSIONTYPE", referencedColumnName="ID_COMMISSIONTYPE", nullable=false, options={"unsigned":true})
     */
    private $type;

    /**
     * @var Collection<int, Utilisateur>
     *
     * @ORM\ManyToMany(targetEntity=Utilisateur::class, mappedBy="commissions")
     */
    private $utilisateurs;

    /**
     * @var Collection<int, CommissionRegle>
     *
     * @ORM\OneToMany(targetEntity=CommissionRegle::class, mappedBy="commission", orphanRemoval=true)
     */
    private $regles;

    public function __construct()
    {
        $this->utilisateurs = new ArrayCollection();
        $this->regles = new ArrayCollection();
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

    public function getDocumentCr(): ?string
    {
        return $this->documentCr;
    }

    public function setDocumentCr(?string $documentCr): self
    {
        $this->documentCr = $documentCr;

        return $this;
    }

    public function getType(): ?CommissionType
    {
        return $this->type;
    }

    public function setType(?CommissionType $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, Utilisateur>
     */
    public function getUtilisateurs(): Collection
    {
        return $this->utilisateurs;
    }

    public function addUtilisateur(Utilisateur $utilisateur): self
    {
        if (!$this->utilisateurs->contains($utilisateur)) {
            $this->utilisateurs[] = $utilisateur;
            $utilisateur->addCommission($this);
        }

        return $this;
    }

    public function removeUtilisateur(Utilisateur $utilisateur): self
    {
        if ($this->utilisateurs->removeElement($utilisateur)) {
            $utilisateur->removeCommission($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, CommissionRegle>
     */
    public function getRegles(): Collection
    {
        return $this->regles;
    }

    public function addRegle(CommissionRegle $regle): self
    {
        if (!$this->regles->contains($regle)) {
            $this->regles[] = $regle;
            $regle->setCommission($this);
        }

        return $this;
    }

    public function removeRegle(CommissionRegle $regle): self
    {
        // set the owning side to null (unless already changed)
        if ($this->regles->removeElement($regle) && $regle->getCommission() === $this) {
            $regle->setCommission(null);
        }

        return $this;
    }
}
