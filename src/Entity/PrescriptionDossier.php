<?php

namespace App\Entity;

use App\Repository\PrescriptionDossierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PrescriptionDossierRepository::class)
 *
 * @ORM\Table(name="prescriptiondossier")
 */
class PrescriptionDossier
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_PRESCRIPTION_DOSSIER", type="bigint")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="NUM_PRESCRIPTION_DOSSIER", type="integer", nullable=false)
     */
    private $numero;

    /**
     * @var ?string
     *
     * @ORM\Column(name="LIBELLE_PRESCRIPTION_DOSSIER", type="text", nullable=true)
     */
    private $libelle;

    /**
     * @var int
     *
     * @ORM\Column(name="TYPE_PRESCRIPTION_DOSSIER", type="integer", nullable=false)
     */
    private $type;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATE_LEVEE", type="date", nullable=true)
     */
    private $dateLevee;

    /**
     * @var ?string
     *
     * @ORM\Column(name="JUSTIFICATIF_LEVEE", type="text", nullable=true)
     */
    private $justificatif;

    /**
     * @var ?Dossier
     *
     * @ORM\ManyToOne(targetEntity=Dossier::class)
     *
     * @ORM\JoinColumn(name="ID_DOSSIER_REPRISE", referencedColumnName="ID_DOSSIER", nullable=true)
     */
    private $dossierReprise;

    /**
     * @var ?Dossier
     *
     * @ORM\ManyToOne(targetEntity=Dossier::class, inversedBy="prescriptions")
     *
     * @ORM\JoinColumn(name="ID_DOSSIER", referencedColumnName="ID_DOSSIER", nullable=false)
     */
    private $dossier;

    /**
     * @var ?PrescriptionType
     *
     * @ORM\ManyToOne(targetEntity=PrescriptionType::class)
     *
     * @ORM\JoinColumn(name="ID_PRESCRIPTION_TYPE", referencedColumnName="ID_PRESCRIPTIONTYPE", nullable=true)
     */
    private $prescriptionType;

    /**
     * @var Collection<int, PrescriptionDossierAssociation>
     *
     * @ORM\OneToMany(targetEntity=PrescriptionDossierAssociation::class, mappedBy="prescriptionDossier", orphanRemoval=true)
     */
    private $associations;

    public function __construct()
    {
        $this->associations = new ArrayCollection();
    }

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

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getDateLevee(): ?\DateTimeInterface
    {
        return $this->dateLevee;
    }

    public function setDateLevee(?\DateTimeInterface $dateLevee): self
    {
        $this->dateLevee = $dateLevee;

        return $this;
    }

    public function getJustificatif(): ?string
    {
        return $this->justificatif;
    }

    public function setJustificatif(?string $justificatif): self
    {
        $this->justificatif = $justificatif;

        return $this;
    }

    public function getDossierReprise(): ?Dossier
    {
        return $this->dossierReprise;
    }

    public function setDossierReprise(?Dossier $dossierReprise): self
    {
        $this->dossierReprise = $dossierReprise;

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

    public function getPrescriptionType(): ?PrescriptionType
    {
        return $this->prescriptionType;
    }

    public function setPrescriptionType(?PrescriptionType $prescriptionType): self
    {
        $this->prescriptionType = $prescriptionType;

        return $this;
    }

    /**
     * @return Collection<int, PrescriptionDossierAssociation>
     */
    public function getAssociations(): Collection
    {
        return $this->associations;
    }

    public function addAssociation(PrescriptionDossierAssociation $association): self
    {
        if (!$this->associations->contains($association)) {
            $this->associations[] = $association;
            $association->setPrescriptionDossier($this);
        }

        return $this;
    }

    public function removeAssociation(PrescriptionDossierAssociation $association): self
    {
        // set the owning side to null (unless already changed)
        if ($this->associations->removeElement($association) && $association->getPrescriptionDossier() === $this) {
            $association->setPrescriptionDossier(null);
        }

        return $this;
    }
}
