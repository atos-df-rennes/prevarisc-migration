<?php

namespace App\Entity;

use App\Doctrine\ContactCriteria;
use App\Repository\GroupementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GroupementRepository::class)]
class Groupement
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_GROUPEMENT', type: 'bigint', options: ['unsigned' => true])]
    #[Groups(['api_response'])]
    private $id;

    #[ORM\Column(name: 'LIBELLE_GROUPEMENT', type: 'string', length: 255, nullable: false)]
    #[Groups(['api_response'])]
    private ?string $libelle = null;

    #[ORM\OneToOne(targetEntity: UtilisateurInformations::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'ID_UTILISATEURINFORMATIONS', referencedColumnName: 'ID_UTILISATEURINFORMATIONS', options: ['unsigned' => true])]
    private ?UtilisateurInformations $informations = null;

    #[ORM\ManyToOne(targetEntity: GroupementType::class, inversedBy: 'groupements')]
    #[ORM\JoinColumn(name: 'ID_GROUPEMENTTYPE', referencedColumnName: 'ID_GROUPEMENTTYPE', nullable: false)]
    #[Groups(['api_response'])]
    private ?GroupementType $type = null;

    /**
     * @var Collection<int, Utilisateur>
     */
    #[ORM\ManyToMany(targetEntity: Utilisateur::class, mappedBy: 'groupements')]
    private ArrayCollection|array $utilisateurs;

    /**
     * @var Collection<int, Commune>
     */
    #[ORM\JoinTable(name: 'groupementcommune')]
    #[ORM\JoinColumn(name: 'ID_GROUPEMENT', referencedColumnName: 'ID_GROUPEMENT', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'NUMINSEE_COMMUNE', referencedColumnName: 'NUMINSEE_COMMUNE', options: ['fixed' => true])]
    #[ORM\ManyToMany(targetEntity: Commune::class, inversedBy: 'groupements')]
    private ArrayCollection|array $communes;

    /**
     * @var Collection<int, UtilisateurInformations>
     */
    #[ORM\JoinTable(name: 'groupementcontact')]
    #[ORM\JoinColumn(name: 'ID_GROUPEMENT', referencedColumnName: 'ID_GROUPEMENT', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_UTILISATEURINFORMATIONS', referencedColumnName: 'ID_UTILISATEURINFORMATIONS', options: ['unsigned' => true])]
    #[ORM\ManyToMany(targetEntity: UtilisateurInformations::class)]
    private ArrayCollection|array $contacts;

    /*
     * La table de jointure contient plus de champs avec des dates
     * mais elles ne sont jamais utilisées.
     */
    /**
     * @var Collection<int, Utilisateur>
     */
    #[ORM\JoinTable(name: 'groupementpreventionniste')]
    #[ORM\JoinColumn(name: 'ID_GROUPEMENT', referencedColumnName: 'ID_GROUPEMENT', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_UTILISATEUR', referencedColumnName: 'ID_UTILISATEUR', options: ['unsigned' => true])]
    #[ORM\ManyToMany(targetEntity: Utilisateur::class)]
    private ArrayCollection|array $preventionnistes;

    public function __construct()
    {
        $this->utilisateurs = new ArrayCollection();
        $this->communes = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->preventionnistes = new ArrayCollection();
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

    /**
     * @return Collection<int, UtilisateurInformations>
     */
    public function getSortedContacts(): Collection
    {
        assert($this->contacts instanceof Selectable);

        /* @phpstan-ignore-next-line */
        return $this->contacts->matching(ContactCriteria::sortedByName());
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

    /**
     * @return Collection<int, Utilisateur>
     */
    public function getPreventionnistes(): Collection
    {
        return $this->preventionnistes;
    }

    public function addPreventionniste(Utilisateur $preventionniste): self
    {
        if (!$this->preventionnistes->contains($preventionniste)) {
            $this->preventionnistes[] = $preventionniste;
        }

        return $this;
    }

    public function removePreventionniste(Utilisateur $preventionniste): self
    {
        $this->preventionnistes->removeElement($preventionniste);

        return $this;
    }
}
