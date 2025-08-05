<?php

namespace App\Entity;

use App\Repository\DocManquantRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DocManquantRepository::class)
 * @ORM\Table(name="docmanquant")
 */
class DocManquant
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="ID_DOCMANQUANT", type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="LIBELLE_DOCMANQUANT", type="text")
     */
    private $libelleDocManquant;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelleDocManquant(): ?string
    {
        return $this->libelleDocManquant;
    }

    public function setLibelleDocManquant(string $libelleDocManquant): self
    {
        $this->libelleDocManquant = $libelleDocManquant;

        return $this;
    }
}
