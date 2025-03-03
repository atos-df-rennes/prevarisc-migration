<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UtilisateurRepository::class)
 *
 * @UniqueEntity(fields="username", message="Nom d'utilisateur déjà enregistré. Veuillez en choisir un autre.")
 */
class Utilisateur implements UserInterface
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_UTILISATEUR", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="USERNAME_UTILISATEUR", type="string", length=100, unique=true)
     *
     * @Assert\NotBlank
     */
    private $username;

    /**
     * @var array<Role|string>
     *
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /** @var string */
    private $plainPassword;

    // @todo: Ajouter la règle de validation si enforce_security est à true
    /**
     * @var ?string The hashed password
     *
     * @ORM\Column(name="PASSWD_UTILISATEUR", type="string", length=255, nullable=true)
     */
    private $password;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="LASTACTION_UTILISATEUR", type="datetime", nullable=true)
     */
    private $lastAction;

    /**
     * @var bool
     *
     * @ORM\Column(name="ACTIF_UTILISATEUR", type="boolean")
     */
    private $actif;

    /**
     * @var int
     *
     * @ORM\Column(name="FAILED_LOGIN_ATTEMPTS_UTILISATEUR", type="smallint")
     */
    private $failedLoginAttempts = 0;

    /**
     * @var ?string
     *
     * @ORM\Column(name="IP_UTILISATEUR", type="string", length=45, nullable=true)
     */
    private $ip;

    /**
     * @var ?string
     *
     * @ORM\Column(name="NUMINSEE_COMMUNE", type="string", length=5, nullable=true)
     */
    private $numinsee;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DERNIERE_VISITE_DASHBOARD", type="datetime", nullable=true)
     */
    private $derniereVisiteDashboard;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DERNIERE_VISITE_PIECES_DOSSIER", type="datetime", nullable=true)
     */
    private $derniereVisitePiecesDossier;

    /**
     * @var ?Groupe
     *
     * @ORM\ManyToOne(targetEntity=Groupe::class, inversedBy="utilisateurs")
     *
     * @ORM\JoinColumn(name="ID_GROUPE", referencedColumnName="ID_GROUPE", options={"unsigned":true})
     */
    private $groupe;

    /**
     * @var ?Utilisateurinformations
     *
     * @ORM\OneToOne(targetEntity=Utilisateurinformations::class, cascade={"persist", "remove"})
     *
     * @ORM\JoinColumn(name="ID_UTILISATEURINFORMATIONS", referencedColumnName="ID_UTILISATEURINFORMATIONS", nullable=false, options={"unsigned":true})
     */
    private $informations;

    /**
     * @var UtilisateurPreferences
     *
     * @ORM\OneToOne(targetEntity=UtilisateurPreferences::class, mappedBy="utilisateur", cascade={"persist", "remove"})
     */
    private $preferences;

    /**
     * @var ?Commune
     *
     * @ORM\ManyToOne(targetEntity=Commune::class, inversedBy="utilisateurs")
     *
     * @ORM\JoinColumn(name="NUMINSEE_COMMUNE", referencedColumnName="NUMINSEE_COMMUNE", nullable=true)
     */
    private $commune;

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
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
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

    public function getInformations(): ?Utilisateurinformations
    {
        return $this->informations;
    }

    public function setInformations(Utilisateurinformations $informations): self
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
}
