<?php

namespace App\Entity;

use App\Repository\CapsuleRubriqueRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CapsuleRubriqueRepository::class)
 * 
 * @ORM\Table(name="capsulerubrique")
 */
class CapsuleRubrique
{
    /**
     * @var int
     * 
     * @ORM\Id
     * 
     * @ORM\GeneratedValue
     * 
     * @ORM\Column(name="ID_CAPSULERUBRIQUE", type="integer")
     */
    private $id;

    /**
     * @var string
     * 
     * @ORM\Column(name="NOM_INTERNE", type="string", length=255)
     */
    private $nomInterne;

    /**
     * @var string
     * 
     * @ORM\Column(name="NOM", type="string", length=255)
     */
    private $nom;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomInterne(): ?string
    {
        return $this->nomInterne;
    }

    public function setNomInterne(string $nomInterne): self
    {
        $this->nomInterne = $nomInterne;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }
}
