<?php

namespace App\Entity;

use App\Repository\FonctionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FonctionRepository::class)
 */
class Fonction
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_FONCTION", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_FONCTION", type="string", length=50)
     */
    private $libelle;

    /**
     * @var Collection<int, UtilisateurInformations>
     *
     * @ORM\OneToMany(targetEntity=UtilisateurInformations::class, mappedBy="fonction")
     */
    private $utilisateurInformations;

    public function __construct()
    {
        $this->utilisateurInformations = new ArrayCollection();
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
     * @return Collection<int, UtilisateurInformations>
     */
    public function getUtilisateurInformations(): Collection
    {
        return $this->utilisateurInformations;
    }

    public function addUtilisateurInformation(UtilisateurInformations $utilisateurInformation): self
    {
        if (!$this->utilisateurInformations->contains($utilisateurInformation)) {
            $this->utilisateurInformations[] = $utilisateurInformation;
            $utilisateurInformation->setFonction($this);
        }

        return $this;
    }

    public function removeUtilisateurInformation(UtilisateurInformations $utilisateurInformation): self
    {
        // set the owning side to null (unless already changed)
        if ($this->utilisateurInformations->removeElement($utilisateurInformation) && $utilisateurInformation->getFonction() === $this) {
            $utilisateurInformation->setFonction(null);
        }

        return $this;
    }
}
