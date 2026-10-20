<?php

namespace App\Entity;

use App\Repository\ValeurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ValeurRepository::class)]
class Valeur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_VALEUR', type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'VALEUR_STR', type: Types::STRING, length: 255, nullable: true)]
    private ?string $str = null;

    #[ORM\Column(name: 'VALEUR_LONG_STR', type: Types::TEXT, nullable: true)]
    private ?string $longStr = null;

    #[ORM\Column(name: 'VALEUR_INT', type: Types::INTEGER, nullable: true)]
    private ?int $number = null;

    #[ORM\Column(name: 'VALEUR_CHECKBOX', type: Types::BOOLEAN, nullable: true)]
    private ?bool $checkbox = null;

    #[ORM\Column(name: 'VALEUR_DATE', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $idx = null;

    #[ORM\ManyToOne(targetEntity: Champ::class, inversedBy: 'valeurs')]
    #[ORM\JoinColumn(name: 'ID_CHAMP', referencedColumnName: 'ID_CHAMP', nullable: false, onDelete: 'CASCADE')]
    private ?Champ $champ = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStr(): ?string
    {
        return $this->str;
    }

    public function setStr(?string $str): self
    {
        $this->str = $str;

        return $this;
    }

    public function getLongStr(): ?string
    {
        return $this->longStr;
    }

    public function setLongStr(?string $longStr): self
    {
        $this->longStr = $longStr;

        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(?int $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getCheckbox(): ?bool
    {
        return $this->checkbox;
    }

    public function setCheckbox(?bool $checkbox): self
    {
        $this->checkbox = $checkbox;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getIdx(): ?int
    {
        return $this->idx;
    }

    public function setIdx(?int $idx): self
    {
        $this->idx = $idx;

        return $this;
    }

    public function getChamp(): ?Champ
    {
        return $this->champ;
    }

    public function setChamp(?Champ $champ): self
    {
        $this->champ = $champ;

        return $this;
    }
}
