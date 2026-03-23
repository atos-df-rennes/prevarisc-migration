<?php

namespace App\Entity;

use App\Repository\PrescriptionReglementaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PrescriptionReglementaireRepository::class)
 *
 * @ORM\Table(name="prescriptionregl")
 */
class PrescriptionReglementaire
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_PRESCRIPTIONREGL", type="bigint")
     */
    private $id;

    /**
     * @var ?int
     *
     * @ORM\Column(name="PRESCRIPTIONREGL_TYPE", type="integer", nullable=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="PRESCRIPTIONREGL_LIBELLE", type="text", nullable=false)
     */
    private $libelle;

    /**
     * @var bool
     *
     * @ORM\Column(name="PRESCRIPTIONREGL_VISIBLE", type="boolean", nullable=false)
     */
    private $visible;

    /**
     * @var Collection<int, PrescriptionReglementaireAssociation>
     *
     * @ORM\OneToMany(targetEntity=PrescriptionReglementaireAssociation::class, mappedBy="regle", orphanRemoval=true, cascade={"persist"})
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

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(?int $type): self
    {
        $this->type = $type;

        return $this;
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

    public function getVisible(): ?bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * @return Collection<int, PrescriptionReglementaireAssociation>
     */
    public function getAssociations(): Collection
    {
        return $this->associations;
    }

    /**
     * @param Collection<int, PrescriptionReglementaireAssociation> $associations
     */
    public function setAssociations(Collection $associations): self
    {
        $this->associations->clear();

        foreach ($associations as $key => $association) {
            $association->setNumero($key + 1);
            $association->setRegle($this);

            $this->associations->add($association);
        }

        return $this;
    }

    public function addAssociation(PrescriptionReglementaireAssociation $association): self
    {
        if (!$this->associations->contains($association)) {
            if (($lastAsso = $this->associations->last()) instanceof PrescriptionReglementaireAssociation) {
                $lastNumero = $lastAsso->getNumero();
            } else {
                $lastNumero = $this->associations->count();
            }

            $association->setNumero($lastNumero + 1);
            $association->setRegle($this);

            $this->associations->add($association);
        }

        return $this;
    }

    public function removeAssociation(PrescriptionReglementaireAssociation $association): self
    {
        // set the owning side to null (unless already changed)
        if ($this->associations->removeElement($association) && $association->getRegle() === $this) {
            $association->setRegle(null);
        }

        return $this;
    }
}
