<?php

namespace App\Entity;

use App\Repository\EtablissementDisplayRubriqueRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EtablissementDisplayRubriqueRepository::class)
 *
 * @ORM\Table(name="displayrubriqueetablissement")
 */
class EtablissementDisplayRubrique
{
    /**
     * @var ?Etablissement
     *
     * @ORM\Id
     *
     * @ORM\ManyToOne(targetEntity=Etablissement::class, inversedBy="displayRubriques")
     *
     * @ORM\JoinColumn(name="ID_ETABLISSEMENT", referencedColumnName="ID_ETABLISSEMENT", nullable=false)
     */
    private $etablissement;

    /**
     * @var Rubrique
     *
     * @ORM\Id
     *
     * @ORM\ManyToOne(targetEntity=Rubrique::class)
     *
     * @ORM\JoinColumn(name="ID_RUBRIQUE", referencedColumnName="ID_RUBRIQUE", nullable=false)
     */
    private $rubrique;

    /**
     * @var bool
     *
     * @ORM\Column(name="USER_DISPLAY", type="boolean", nullable=false)
     */
    private $userDisplay;

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
}
