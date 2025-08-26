<?php

namespace App\Entity;

use App\Repository\UtilisateurCiviliteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UtilisateurCiviliteRepository::class)
 *
 * @ORM\Table(name="utilisateurcivilite")
 */
class UtilisateurCivilite
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_UTILISATEURCIVILITE", type="integer", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_UTILISATEURCIVILITE", type="string", length=255, nullable=false)
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
