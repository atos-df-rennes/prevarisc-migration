<?php

namespace App\Entity;

use App\Repository\EtablissementCategorieRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EtablissementCategorieRepository::class)
 *
 * @ORM\Table(name="categorie")
 */
class EtablissementCategorie
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_CATEGORIE", type="integer", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_CATEGORIE", type="string", length=15)
     */
    private $libelle;

    /**
     * @var ?string
     *
     * @ORM\Column(name="COMMENTAIRE_CATEGORIE", type="string", length=35, nullable=true)
     */
    private $commentaire;

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
