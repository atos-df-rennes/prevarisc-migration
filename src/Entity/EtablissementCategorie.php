<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\EtablissementCategorieRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtablissementCategorieRepository::class)]
#[ORM\Table(name: 'categorie')]
class EtablissementCategorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_CATEGORIE', type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'LIBELLE_CATEGORIE', type: Types::STRING, length: 15, nullable: false)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'COMMENTAIRE_CATEGORIE', type: Types::STRING, length: 35, nullable: true)]
    private ?string $commentaire = null;

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

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;

        return $this;
    }
}
