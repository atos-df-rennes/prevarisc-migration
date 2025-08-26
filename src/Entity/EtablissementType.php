<?php

namespace App\Entity;

use App\Repository\EtablissementTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EtablissementTypeRepository::class)
 *
 * @ORM\Table(name="type")
 */
class EtablissementType
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_TYPE", type="integer", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_TYPE", type="string", length=45, nullable=false)
     */
    private $libelle;

    /**
     * @var Collection<int, EtablissementActivite>
     *
     * @ORM\OneToMany(targetEntity=EtablissementActivite::class, mappedBy="type", orphanRemoval=true)
     */
    private $activites;

    public function __construct()
    {
        $this->activites = new ArrayCollection();
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

    /**
     * @return Collection<int, EtablissementActivite>
     */
    public function getActivites(): Collection
    {
        return $this->activites;
    }

    public function addActivite(EtablissementActivite $activite): self
    {
        if (!$this->activites->contains($activite)) {
            $this->activites[] = $activite;
            $activite->setType($this);
        }

        return $this;
    }

    public function removeActivite(EtablissementActivite $activite): self
    {
        // set the owning side to null (unless already changed)
        if ($this->activites->removeElement($activite) && $activite->getType() === $this) {
            $activite->setType(null);
        }

        return $this;
    }
}
