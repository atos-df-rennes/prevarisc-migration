<?php

namespace App\Entity;

use App\Repository\DossierNatureListeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=DossierNatureListeRepository::class)
 *
 * @ORM\Table(name="dossiernatureliste")
 */
class DossierNatureListe
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_DOSSIERNATURE", type="bigint")
     *
     * @Groups({"api_response"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_DOSSIERNATURE", type="string", length=100, nullable=false)
     *
     * @Groups({"api_response"})
     */
    private $libelle;

    /**
     * @var DossierType
     *
     * @ORM\ManyToOne(targetEntity=DossierType::class)
     *
     * @ORM\JoinColumn(name="ID_DOSSIERTYPE", referencedColumnName="ID_DOSSIERTYPE", nullable=false, options={"unsigned":true})
     */
    private $type;

    /**
     * @var ?int
     *
     * @ORM\Column(name="ORDRE", type="integer", nullable=true)
     */
    private $ordre;

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

    public function getType(): ?DossierType
    {
        return $this->type;
    }

    public function setType(DossierType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(?int $ordre): self
    {
        $this->ordre = $ordre;

        return $this;
    }
}
