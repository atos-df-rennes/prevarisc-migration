<?php

namespace App\Entity;

use App\Repository\DocumentManquantRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DocumentManquantRepository::class)
 *
 * @ORM\Table(name="docmanquant")
 */
class DocumentManquant
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_DOCMANQUANT", type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_DOCMANQUANT", type="text", nullable=false)
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

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }
}
