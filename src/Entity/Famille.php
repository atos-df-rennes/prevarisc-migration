<?php

namespace App\Entity;

use App\Repository\FamilleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FamilleRepository::class)
 *
 * @ORM\Table(name="famille")
 */
class Famille
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer", name="ID_FAMILLE", options={"unsigned":true})
     */
    private $id;

    /**
     * @var ?string
     *
     * @ORM\Column(type="string", length=100, nullable=true, name="LIBELLE_FAMILLE")
     */
    private $libelle;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }
}
