<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\UtilisateurPreferencesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UtilisateurPreferencesRepository::class)]
#[ORM\Table(name: 'utilisateurpreferences')]
class UtilisateurPreferences
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_UTILISATEURPREFERENCES', type: Types::BIGINT, options: ['unsigned' => true])]
    private ?string $id = null;

    #[ORM\Column(name: 'DASHBOARD_BLOCS', type: Types::STRING, length: 500, nullable: true)]
    private ?string $blocsDashboard = null;

    #[ORM\OneToOne(targetEntity: Utilisateur::class, inversedBy: 'preferences', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'ID_UTILISATEUR', referencedColumnName: 'ID_UTILISATEUR', nullable: false, options: ['unsigned' => true])]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(name: 'DERNIERE_VERSION_VUE', type: Types::STRING, length: 50, nullable: true)]
    private ?string $derniereVersionVue = null;

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

    public function getDerniereVersionVue(): ?string
    {
        return $this->derniereVersionVue;
    }

    public function setDerniereVersionVue(?string $version): self
    {
        $this->derniereVersionVue = $version;

        return $this;
    }
}
