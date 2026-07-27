<?php

namespace App\Entity;

use App\Repository\TexteApplicableRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TexteApplicableRepository::class)]
#[ORM\Table(name: 'textesappl')]
class TexteApplicable
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_TEXTESAPPL', type: 'bigint')]
    private $id;

    #[ORM\Column(name: 'LIBELLE_TEXTESAPPL', type: 'text', nullable: true)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'VISIBLE_TEXTESAPPL', type: 'boolean', nullable: true)]
    private ?bool $visible = null;

    #[ORM\ManyToOne(targetEntity: TypeTexteApplicable::class)]
    #[ORM\JoinColumn(name: 'ID_TYPETEXTEAPPL', referencedColumnName: 'ID_TYPETEXTEAPPL', nullable: false)]
    private ?TypeTexteApplicable $type = null;

    #[ORM\Column(name: 'NUM_TEXTESAPPL', type: 'integer', nullable: false)]
    private ?int $num = null;

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

    public function getVisible(): ?bool
    {
        return $this->visible;
    }

    public function setVisible(?bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    public function getType(): ?TypeTexteApplicable
    {
        return $this->type;
    }

    public function setType(TypeTexteApplicable $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getNum(): ?int
    {
        return $this->num;
    }

    public function setNum(int $num): self
    {
        $this->num = $num;

        return $this;
    }
}
