<?php

namespace App\Entity;

use App\Repository\GenreRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GenreRepository::class)
 * 
 * @ORM\Table(name="genre")
 */
class Genre
{
    /**
     * @var int
     * 
     * @ORM\Id
     * 
     * @ORM\GeneratedValue
     * 
     * @ORM\Column(name="ID_GENRE", type="integer")
     */
    private $id;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="LIBELLE_GENRE", type="string", length=50, nullable=true)
     */
    private $libelleGenre;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelleGenre(): ?string
    {
        return $this->libelleGenre;
    }

    public function setLibelleGenre(?string $libelleGenre): self
    {
        $this->libelleGenre = $libelleGenre;

        return $this;
    }
}
