<?php

namespace App\Entity;

use App\Repository\DossierDisplayRubriqueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DossierDisplayRubriqueRepository::class)]
#[ORM\Table(name: 'displayrubriquedossier')]
class DossierDisplayRubrique
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Dossier::class, inversedBy: 'displayRubriques')]
    #[ORM\JoinColumn(name: 'ID_DOSSIER', referencedColumnName: 'ID_DOSSIER', nullable: false, onDelete: 'CASCADE')]
    private ?Dossier $dossier = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Rubrique::class)]
    #[ORM\JoinColumn(name: 'ID_RUBRIQUE', referencedColumnName: 'ID_RUBRIQUE', nullable: false, onDelete: 'CASCADE')]
    private ?Rubrique $rubrique = null;

    #[ORM\Column(name: 'USER_DISPLAY', type: Types::BOOLEAN, nullable: true, options: ['default' => 0])]
    private ?bool $userDisplay = null;

    #[ORM\Column(name: 'USER_OPENED', type: Types::BOOLEAN, nullable: true)]
    private ?bool $userOpened = null;

    public function getDossier(): ?Dossier
    {
        return $this->dossier;
    }

    public function setDossier(?Dossier $dossier): self
    {
        $this->dossier = $dossier;

        return $this;
    }

    public function getRubrique(): ?Rubrique
    {
        return $this->rubrique;
    }

    public function setRubrique(Rubrique $rubrique): self
    {
        $this->rubrique = $rubrique;

        return $this;
    }

    public function getUserDisplay(): ?bool
    {
        return $this->userDisplay;
    }

    public function setUserDisplay(bool $userDisplay): self
    {
        $this->userDisplay = $userDisplay;

        return $this;
    }

    public function getUserOpened(): ?bool
    {
        return $this->userOpened;
    }

    public function setUserOpened(?bool $userOpened): self
    {
        $this->userOpened = $userOpened;

        return $this;
    }
}
