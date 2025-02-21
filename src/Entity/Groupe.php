<?php

namespace App\Entity;

use App\Repository\GroupeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GroupeRepository::class)
 */
class Groupe
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_GROUPE", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_GROUPE", type="string", length=255)
     */
    private $libelle;

    /**
     * @var string
     *
     * @ORM\Column(name="DESC_GROUPE", type="text")
     */
    private $description;

    /**
     * @var Collection<int, Utilisateur>
     *
     * @ORM\OneToMany(targetEntity=Utilisateur::class, mappedBy="groupe")
     */
    private $utilisateurs;

    /**
     * @var Collection<int, Privileges>
     *
     * @ORM\JoinTable(name="`groupe-privileges`",
     *   joinColumns={@ORM\JoinColumn(name="ID_GROUPE", referencedColumnName="ID_GROUPE", options={"unsigned":true})},
     *   inverseJoinColumns={@ORM\JoinColumn(name="id_privilege", referencedColumnName="id_privilege", options={"unsigned":true})}
     * )
     *
     * @ORM\ManyToMany(targetEntity=Privileges::class)
     */
    private $privileges;

    public function __construct()
    {
        $this->utilisateurs = new ArrayCollection();
        $this->privileges = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

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
            $utilisateur->setGroupe($this);
        }

        return $this;
    }

    public function removeUtilisateur(Utilisateur $utilisateur): self
    {
        // set the owning side to null (unless already changed)
        if ($this->utilisateurs->removeElement($utilisateur) && $utilisateur->getGroupe() === $this) {
            $utilisateur->setGroupe(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Privileges>
     */
    public function getPrivileges(): Collection
    {
        return $this->privileges;
    }

    public function addPrivilege(Privileges $privilege): self
    {
        if (!$this->privileges->contains($privilege)) {
            $this->privileges[] = $privilege;
        }

        return $this;
    }

    public function removePrivilege(Privileges $privilege): self
    {
        $this->privileges->removeElement($privilege);

        return $this;
    }
}
