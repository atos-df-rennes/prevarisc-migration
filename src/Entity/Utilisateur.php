<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\UtilisateurRepository;
use App\Validator as PrevariscAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @PrevariscAssert\UniqueUtilisateur
 *
 * @PrevariscAssert\Password
 */
#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class Utilisateur implements UserInterface, LegacyPasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_UTILISATEUR', type: Types::BIGINT, options: ['unsigned' => true])]
    #[Groups(['api_response'])]
    private ?string $id = null;

    #[ORM\Column(name: 'USERNAME_UTILISATEUR', type: Types::STRING, length: 100, nullable: false)]
    #[Assert\NotNull(message: "Le nom d'utilisateur ne doit pas être null.")]
    private ?string $username = null;

    /**
     * @var array<Role|string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    private ?string $plainPassword = null;

    /**
     * @var ?string The hashed password
     */
    #[ORM\Column(name: 'PASSWD_UTILISATEUR', type: Types::STRING, length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(name: 'LASTACTION_UTILISATEUR', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastAction = null;

    #[ORM\Column(name: 'ACTIF_UTILISATEUR', type: Types::BOOLEAN, nullable: false)]
    private ?bool $actif = null;

    #[ORM\Column(name: 'FAILED_LOGIN_ATTEMPTS_UTILISATEUR', type: Types::SMALLINT, nullable: false, options: ['unsigned' => true])]
    private int $failedLoginAttempts = 0;

    #[ORM\Column(name: 'IP_UTILISATEUR', type: Types::STRING, length: 45, nullable: true)]
    private ?string $ip = null;

    // @todo: Vérifier si doublon avec $commune
    #[ORM\Column(name: 'NUMINSEE_COMMUNE', type: Types::STRING, length: 5, nullable: true)]
    private ?string $numinsee = null;

    #[ORM\Column(name: 'DERNIERE_VISITE_DASHBOARD', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $derniereVisiteDashboard = null;

    #[ORM\Column(name: 'DERNIERE_VISITE_PIECES_DOSSIER', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $derniereVisitePiecesDossier = null;

    #[ORM\ManyToOne(targetEntity: Groupe::class, inversedBy: 'utilisateurs')]
    #[ORM\JoinColumn(name: 'ID_GROUPE', referencedColumnName: 'ID_GROUPE', nullable: false, options: ['unsigned' => true])]
    private ?Groupe $groupe = null;

    #[ORM\OneToOne(targetEntity: UtilisateurInformations::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'ID_UTILISATEURINFORMATIONS', referencedColumnName: 'ID_UTILISATEURINFORMATIONS', nullable: false, options: ['unsigned' => true])]
    private ?UtilisateurInformations $informations = null;

    #[ORM\OneToOne(targetEntity: UtilisateurPreferences::class, mappedBy: 'utilisateur', cascade: ['persist', 'remove'])]
    private ?UtilisateurPreferences $preferences = null;

    #[ORM\ManyToOne(targetEntity: Commune::class, inversedBy: 'utilisateurs')]
    #[ORM\JoinColumn(name: 'NUMINSEE_COMMUNE', referencedColumnName: 'NUMINSEE_COMMUNE', nullable: true)]
    private ?Commune $commune = null;

    /**
     * @var Collection<int, Commission>
     */
    #[ORM\JoinTable(name: 'utilisateurcommission')]
    #[ORM\JoinColumn(name: 'ID_UTILISATEUR', referencedColumnName: 'ID_UTILISATEUR', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_COMMISSION', referencedColumnName: 'ID_COMMISSION', options: ['unsigned' => true])]
    #[ORM\ManyToMany(targetEntity: Commission::class, inversedBy: 'utilisateurs')]
    private Collection $commissions;

    /**
     * @var Collection<int, Groupement>
     */
    #[ORM\JoinTable(name: 'utilisateurgroupement')]
    #[ORM\JoinColumn(name: 'ID_UTILISATEUR', referencedColumnName: 'ID_UTILISATEUR', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_GROUPEMENT', referencedColumnName: 'ID_GROUPEMENT', options: ['unsigned' => true])]
    #[ORM\ManyToMany(targetEntity: Groupement::class, inversedBy: 'utilisateurs')]
    private Collection $groupements;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $sso = null;

    #[Groups(['api_response'])]
    private ?string $displayLabel = null;

    public function __construct()
    {
        $this->commissions = new ArrayCollection();
        $this->groupements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function getUserIdentifier(): string
    {
        $username = $this->getUsername();

        if ('' === $username) {
            throw new \LogicException('Utilisateur::getUserIdentifier() ne peut pas retourner une chaîne vide.');
        }

        return $username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param array<Role|string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * Retourne le username en tant que sel pour l'encodeur MD5 legacy (Md5SaltedEncoder).
     * Les encodeurs modernes (bcrypt/argon2) ignorent ce champ.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        // Retourné pour l'encodeur MD5 legacy via migrate_from.
        // Les encodeurs modernes (bcrypt/argon2) ignorent ce champ.
        return $this->username;
    }

    /**
     * Retirée du cycle d'authentification Symfony (AuthenticatorManager) depuis
     * la dépréciation de eraseCredentials() en 7.3 : à l'authentification, l'entité
     * est toujours fraîchement rechargée par le UserProvider, donc plainPassword
     * y est déjà null. Cette méthode ne reste utile que pour le flux de
     * création/édition via formulaire (hors authentification), d'où l'attribut
     * #[\Deprecated] qui signale à Symfony de ne plus l'invoquer automatiquement.
     *
     * @see UserInterface
     */
    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
    }

    public function getLastAction(): ?\DateTimeInterface
    {
        return $this->lastAction;
    }

    public function setLastAction(?\DateTimeInterface $lastAction): self
    {
        $this->lastAction = $lastAction;

        return $this;
    }

    public function getActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): self
    {
        $this->actif = $actif;

        return $this;
    }

    public function getFailedLoginAttempts(): ?int
    {
        return $this->failedLoginAttempts;
    }

    public function setFailedLoginAttempts(int $failedLoginAttempts): self
    {
        $this->failedLoginAttempts = $failedLoginAttempts;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getNuminsee(): ?string
    {
        return $this->numinsee;
    }

    public function setNuminsee(?string $numinsee): self
    {
        $this->numinsee = $numinsee;

        return $this;
    }

    public function getDerniereVisiteDashboard(): ?\DateTimeInterface
    {
        return $this->derniereVisiteDashboard;
    }

    public function setDerniereVisiteDashboard(?\DateTimeInterface $derniereVisiteDashboard): self
    {
        $this->derniereVisiteDashboard = $derniereVisiteDashboard;

        return $this;
    }

    public function getDerniereVisitePiecesDossier(): ?\DateTimeInterface
    {
        return $this->derniereVisitePiecesDossier;
    }

    public function setDerniereVisitePiecesDossier(?\DateTimeInterface $derniereVisitePiecesDossier): self
    {
        $this->derniereVisitePiecesDossier = $derniereVisitePiecesDossier;

        return $this;
    }

    public function getGroupe(): ?Groupe
    {
        return $this->groupe;
    }

    public function setGroupe(?Groupe $groupe): self
    {
        $this->groupe = $groupe;

        return $this;
    }

    public function getInformations(): ?UtilisateurInformations
    {
        return $this->informations;
    }

    public function setInformations(UtilisateurInformations $informations): self
    {
        $this->informations = $informations;

        return $this;
    }

    public function isConnected(): bool
    {
        // L'utilisateur ne s'est jamais connecté.
        if (null === $this->getLastAction()) {
            return false;
        }

        $maxLifetime = filter_var(ini_get('session.gc_maxlifetime'), FILTER_VALIDATE_INT);
        $diff = $this->getLastAction()->diff(new \DateTime(date('Y-m-d H:i:s', time() - $maxLifetime)));

        return 0 !== $diff->invert;
    }

    public function getPreferences(): ?UtilisateurPreferences
    {
        return $this->preferences;
    }

    public function setPreferences(UtilisateurPreferences $preferences): self
    {
        // set the owning side of the relation if necessary
        if ($preferences->getUtilisateur() !== $this) {
            $preferences->setUtilisateur($this);
        }

        $this->preferences = $preferences;

        return $this;
    }

    public function getCommune(): ?Commune
    {
        return $this->commune;
    }

    public function setCommune(?Commune $commune): self
    {
        $this->commune = $commune;

        return $this;
    }

    /**
     * @return Collection<int, Commission>
     */
    public function getCommissions(): Collection
    {
        return $this->commissions;
    }

    public function addCommission(Commission $commission): self
    {
        if (!$this->commissions->contains($commission)) {
            $this->commissions[] = $commission;
        }

        return $this;
    }

    public function removeCommission(Commission $commission): self
    {
        $this->commissions->removeElement($commission);

        return $this;
    }

    /**
     * @return Collection<int, Groupement>
     */
    public function getGroupements(): Collection
    {
        return $this->groupements;
    }

    public function addGroupement(Groupement $groupement): self
    {
        if (!$this->groupements->contains($groupement)) {
            $this->groupements[] = $groupement;
        }

        return $this;
    }

    public function removeGroupement(Groupement $groupement): self
    {
        $this->groupements->removeElement($groupement);

        return $this;
    }

    public function getSso(): ?bool
    {
        return $this->sso;
    }

    public function setSso(?bool $sso): self
    {
        $this->sso = $sso;

        return $this;
    }

    /**
     * Retourne le label d'affichage pour les formulaires.
     * Affiche le nom complet si disponible, sinon le username.
     * Ajoute "(inactif)" si l'utilisateur est inactif.
     */
    public function getDisplayLabel(): string
    {
        $label = $this->username;

        $info = $this->getInformations();
        if (null !== $info) {
            $fullName = $info->getFullName();
            if ('' !== $fullName) {
                $label = $fullName;
            }
        }

        $this->displayLabel = $label.($this->actif ? '' : ' (inactif)');

        return $this->displayLabel;
    }
}
