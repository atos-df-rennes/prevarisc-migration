<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\ChampValeurListeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChampValeurListeRepository::class)]
#[ORM\Table(name: 'champvaleurliste')]
class ChampValeurListe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_VALEURLISTE', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'VALEUR', type: Types::STRING, length: 255, nullable: false)]
    private string $valeur;

    #[ORM\ManyToOne(targetEntity: Champ::class, inversedBy: 'valeursListe')]
    #[ORM\JoinColumn(name: 'ID_CHAMP', referencedColumnName: 'ID_CHAMP', nullable: false)]
    private ?Champ $champ = null;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private ?int $idx = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValeur(): string
    {
        return $this->valeur;
    }

    public function setValeur(string $valeur): self
    {
        $this->valeur = $valeur;

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

    public function getIdx(): ?int
    {
        return $this->idx;
    }

    public function setIdx(int $idx): self
    {
        $this->idx = $idx;

        return $this;
    }
}
