<?php

namespace App\Entity;

use App\Repository\UtilisateurciviliteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UtilisateurciviliteRepository::class)
 */
class Utilisateurcivilite
{
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_UTILISATEURCIVILITE", type="integer", options={"unsigned":true})
     */
    private $id;

    /**
     * @ORM\Column(name="LIBELLE_UTILISATEURCIVILITE", type="string", length=255)
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
