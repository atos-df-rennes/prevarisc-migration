<?php

namespace App\Entity;

use App\Repository\UtilisateurPreferencesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UtilisateurPreferencesRepository::class)
 * @ORM\Table(name="utilisateurpreferences")
 */
class UtilisateurPreferences
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="ID_UTILISATEURPREFERENCES", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var ?string
     *
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $blocsDashboard;

    /**
     * @var Utilisateur
     *
     * @ORM\OneToOne(targetEntity=Utilisateur::class, inversedBy="preferences", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="ID_UTILISATEUR", referencedColumnName="ID_UTILISATEUR", nullable=false, options={"unsigned":true})
     */
    private $utilisateur;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBlocsDashboard(): ?string
    {
        return $this->blocsDashboard;
    }

    public function setBlocsDashboard(?string $blocsDashboard): self
    {
        $this->blocsDashboard = $blocsDashboard;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }
}
