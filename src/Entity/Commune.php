<?php

namespace App\Entity;

use App\Repository\CommuneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CommuneRepository::class)
 *
 * @ORM\Table(name="adressecommune")
 */
class Commune
{
    /**
     * @var string
     *
     * @ORM\Id
     *
     * @ORM\Column(name="NUMINSEE_COMMUNE", type="string", length=5, options={"fixed":true})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_COMMUNE", type="string", length=60, nullable=false)
     */
    private $libelle;

    /**
     * @var string
     *
     * @ORM\Column(name="CODEPOSTAL_COMMUNE", type="string", length=5, nullable=false)
     */
    private $codePostal;

    /**
     * @var ?UtilisateurInformations
     *
     * @ORM\OneToOne(targetEntity=UtilisateurInformations::class, cascade={"persist"})
     *
     * @ORM\JoinColumn(name="ID_UTILISATEURINFORMATIONS", referencedColumnName="ID_UTILISATEURINFORMATIONS", nullable=true)
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

    public function getId(): string
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

    public function getUtilisateurInformations(): ?UtilisateurInformations
    {
        return $this->utilisateurInformations;
    }

    public function setUtilisateurInformations(?UtilisateurInformations $utilisateurInformations): self
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
        // set the owning side to null (unless already changed)
        if ($this->utilisateurs->removeElement($utilisateur) && $utilisateur->getCommune() === $this) {
            $utilisateur->setCommune(null);
        }

        return $this;
    }
}
