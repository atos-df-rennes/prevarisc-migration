<?php

namespace App\Entity;

use App\Repository\EtablissementDisplayRubriqueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtablissementDisplayRubriqueRepository::class)]
#[ORM\Table(name: 'displayrubriqueetablissement')]
class EtablissementDisplayRubrique
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Etablissement::class, inversedBy: 'displayRubriques')]
    #[ORM\JoinColumn(name: 'ID_ETABLISSEMENT', referencedColumnName: 'ID_ETABLISSEMENT', nullable: false)]
    private ?Etablissement $etablissement = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Rubrique::class)]
    #[ORM\JoinColumn(name: 'ID_RUBRIQUE', referencedColumnName: 'ID_RUBRIQUE', nullable: false)]
    private ?Rubrique $rubrique = null;

    #[ORM\Column(name: 'USER_DISPLAY', type: Types::BOOLEAN, nullable: false)]
    private ?bool $userDisplay = null;

    #[ORM\Column(name: 'USER_OPENED', type: Types::BOOLEAN, nullable: true)]
    private ?bool $userOpened = null;

    public function getEtablissement(): ?Etablissement
    {
        return $this->etablissement;
    }

    public function setEtablissement(?Etablissement $etablissement): self
    {
        $this->etablissement = $etablissement;

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
