<?php

namespace App\Entity;

use App\Repository\PrescriptionArticleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrescriptionArticleRepository::class)]
#[ORM\Table(name: 'prescriptionarticle')]
class PrescriptionArticle
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_PRESCRIPTIONARTICLE', type: 'bigint')]
    private $id;

    #[ORM\Column(name: 'LIBELLE_PRESCRIPTIONARTICLE', type: 'string', length: 255, nullable: true)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'NUM_PRESCRIPTIONARTICLE', type: 'integer', nullable: true)]
    private ?int $numero = null;

    #[ORM\ManyToOne(targetEntity: PrescriptionTexte::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(name: 'ID_PRESCRIPTIONTEXTE', referencedColumnName: 'ID_PRESCRIPTIONTEXTE', nullable: false)]
    private ?PrescriptionTexte $texte = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(?int $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getTexte(): ?PrescriptionTexte
    {
        return $this->texte;
    }

    public function setTexte(?PrescriptionTexte $texte): self
    {
        $this->texte = $texte;

        return $this;
    }
}
