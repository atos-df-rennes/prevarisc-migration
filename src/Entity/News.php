<?php

namespace App\Entity;

use App\Repository\NewsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NewsRepository::class)
 */
class News
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\Column(name="ID_NEWS", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="TYPE_NEWS", type="string", length=100, nullable=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="TEXTE_NEWS", type="text", nullable=false)
     */
    private $texte;

    /**
     * @var Utilisateur
     *
     * @ORM\ManyToOne(targetEntity=Utilisateur::class)
     *
     * @ORM\JoinColumn(name="ID_UTILISATEUR", referencedColumnName="ID_UTILISATEUR", nullable=false, options={"unsigned":true})
     */
    private $utilisateur;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTexte(): ?string
    {
        return $this->texte;
    }

    public function setTexte(string $texte): self
    {
        $this->texte = $texte;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }
}
