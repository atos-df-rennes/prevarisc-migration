<?php

namespace App\Entity;

use App\Repository\EtablissementActiviteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EtablissementActiviteRepository::class)
 *
 * @ORM\Table(name="typeactivite")
 */
class EtablissementActivite
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_TYPEACTIVITE", type="integer", options={"unsigned":true})
     */
    private $id;

    /**
     * @var ?string
     *
     * @ORM\Column(name="LIBELLE_ACTIVITE", type="string", length=255, nullable=false)
     */
    private $libelle;

    /**
     * @var ?EtablissementType
     *
     * @ORM\ManyToOne(targetEntity=EtablissementType::class, inversedBy="activites")
     *
     * @ORM\JoinColumn(name="ID_TYPE", referencedColumnName="ID_TYPE", nullable=false, options={"unsigned":true})
     */
    private $type;

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

    public function getType(): ?EtablissementType
    {
        return $this->type;
    }

    public function setType(?EtablissementType $type): self
    {
        $this->type = $type;

        return $this;
    }
}
