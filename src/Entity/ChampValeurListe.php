<?php

namespace App\Entity;

use App\Repository\ChampValeurListeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="champvaleurliste")
 *
 * @ORM\Entity(repositoryClass=ChampValeurListeRepository::class)
 */
class ChampValeurListe
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_VALEURLISTE", type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="VALEUR", type="string", length=255, nullable=false)
     */
    private $valeur;

    /**
     * @var ?Champ
     *
     * @ORM\ManyToOne(targetEntity=Champ::class, inversedBy="valeursListe")
     *
     * @ORM\JoinColumn(name="ID_CHAMP", referencedColumnName="ID_CHAMP", nullable=false)
     */
    private $champ;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $idx;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValeur(): ?string
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

    public function setIdx(?int $idx): self
    {
        $this->idx = $idx;

        return $this;
    }
}
