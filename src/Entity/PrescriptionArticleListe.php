<?php

namespace App\Entity;

use App\Repository\PrescriptionArticleListeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrescriptionArticleListeRepository::class)]
#[ORM\Table(name: 'prescriptionarticleliste')]
class PrescriptionArticleListe
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_ARTICLE', type: 'bigint')]
    private $id;

    #[ORM\Column(name: 'LIBELLE_ARTICLE', type: 'string', length: 255, nullable: false)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'VISIBLE_ARTICLE', type: 'boolean', nullable: true)]
    private ?bool $visible = null;

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

    public function isVisible(): ?bool
    {
        return $this->visible;
    }

    public function setVisible(?bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }
}
