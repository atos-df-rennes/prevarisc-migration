<?php

namespace App\Entity;

use App\Repository\CommuneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CommuneRepository::class)
 * @ORM\Table(name="adressecommune")
 */
class Commune
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="NUMINSEE_COMMUNE", type="string", length=5, options={"fixed":true})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_COMMUNE", type="string", length=60)
     */
    private $libelle;

    /**
     * @var string
     *
     * @ORM\Column(name="CODEPOSTAL_COMMUNE", type="string", length=5)
     */
    private $codePostal;

    /**
     * @var Utilisateurinformations
     *
     * @ORM\OneToOne(targetEntity=Utilisateurinformations::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="ID_UTILISATEURINFORMATIONS", referencedColumnName="ID_UTILISATEURINFORMATIONS", nullable=false)
     */
    private $utilisateurInformations;

    /**
     * @var Collection<int, Utilisateur>
     *
     * @ORM\OneToMany(targetEntity=Utilisateur::class, mappedBy="commune")
     */
    private $utilisateurs;

    public function __construct()
    {
        $this->utilisateurs = new ArrayCollection();
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

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(string $codePostal): self
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getUtilisateurInformations(): ?Utilisateurinformations
    {
        return $this->utilisateurInformations;
    }

    public function setUtilisateurInformations(Utilisateurinformations $utilisateurInformations): self
    {
        $this->utilisateurInformations = $utilisateurInformations;

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
            $utilisateur->setCommune($this);
        }

        return $this;
    }

    public function removeUtilisateur(Utilisateur $utilisateur): self
    {
        if ($this->utilisateurs->removeElement($utilisateur)) {
            // set the owning side to null (unless already changed)
            if ($utilisateur->getCommune() === $this) {
                $utilisateur->setCommune(null);
            }
        }

        return $this;
    }
}
