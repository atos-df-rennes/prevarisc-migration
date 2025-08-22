<?php

namespace App\Entity;

use App\Repository\GroupementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GroupementRepository::class)
 */
class Groupement
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_GROUPEMENT", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_GROUPEMENT", type="string", length=255, nullable=false)
     */
    private $libelle;

    /**
     * @var ?UtilisateurInformations
     *
     * @ORM\OneToOne(targetEntity=UtilisateurInformations::class, cascade={"persist", "remove"})
     *
     * @ORM\JoinColumn(name="ID_UTILISATEURINFORMATIONS", referencedColumnName="ID_UTILISATEURINFORMATIONS", options={"unsigned":true})
     */
    private $informations;

    /**
     * @var ?GroupementType
     *
     * @ORM\ManyToOne(targetEntity=GroupementType::class, inversedBy="groupements")
     *
     * @ORM\JoinColumn(name="ID_GROUPEMENTTYPE", referencedColumnName="ID_GROUPEMENTTYPE", nullable=false)
     */
    private $type;

    /**
     * @var Collection<int, Utilisateur>
     *
     * @ORM\ManyToMany(targetEntity=Utilisateur::class, mappedBy="groupements")
     */
    private $utilisateurs;

    /**
     * @var Collection<int, Commune>
     *
     * @ORM\JoinTable(
     *     name="groupementcommune",
     *     joinColumns={@ORM\JoinColumn(name="ID_GROUPEMENT", referencedColumnName="ID_GROUPEMENT", options={"unsigned":true})},
     *     inverseJoinColumns={@ORM\JoinColumn(name="NUMINSEE_COMMUNE", referencedColumnName="NUMINSEE_COMMUNE", options={"fixed":true})}
     * )
     *
     * @ORM\ManyToMany(targetEntity=Commune::class)
     */
    private $communes;

    /**
     * @var Collection<int, UtilisateurInformations>
     *
     * @ORM\JoinTable(
     *     name="groupementcontact",
     *     joinColumns={@ORM\JoinColumn(name="ID_GROUPEMENT", referencedColumnName="ID_GROUPEMENT", options={"unsigned":true})},
     *     inverseJoinColumns={@ORM\JoinColumn(name="ID_UTILISATEURINFORMATIONS", referencedColumnName="ID_UTILISATEURINFORMATIONS", options={"unsigned":true})}
     * )
     *
     * @ORM\ManyToMany(targetEntity=UtilisateurInformations::class)
     */
    private $contacts;

    public function __construct()
    {
        $this->utilisateurs = new ArrayCollection();
        $this->communes = new ArrayCollection();
        $this->contacts = new ArrayCollection();
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

    public function getInformations(): ?UtilisateurInformations
    {
        return $this->informations;
    }

    public function setInformations(?UtilisateurInformations $informations): self
    {
        $this->informations = $informations;

        return $this;
    }

    public function getType(): ?GroupementType
    {
        return $this->type;
    }

    public function setType(?GroupementType $type): self
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
            $utilisateur->addGroupement($this);
        }

        return $this;
    }

    public function removeUtilisateur(Utilisateur $utilisateur): self
    {
        if ($this->utilisateurs->removeElement($utilisateur)) {
            $utilisateur->removeGroupement($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Commune>
     */
    public function getCommunes(): Collection
    {
        return $this->communes;
    }

    public function addCommune(Commune $commune): self
    {
        if (!$this->communes->contains($commune)) {
            $this->communes[] = $commune;
        }

        return $this;
    }

    public function removeCommune(Commune $commune): self
    {
        $this->communes->removeElement($commune);

        return $this;
    }

    /**
     * @return Collection<int, UtilisateurInformations>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(UtilisateurInformations $contact): self
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts[] = $contact;
        }

        return $this;
    }

    public function removeContact(UtilisateurInformations $contact): self
    {
        $this->contacts->removeElement($contact);

        return $this;
    }
}
