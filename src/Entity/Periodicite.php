<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\PeriodiciteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PeriodiciteRepository::class)]
class Periodicite
{
    #[ORM\Id]
    #[ORM\Column(name: 'LOCALSOMMEIL_PERIODICITE', type: Types::BOOLEAN, nullable: false)]
    private bool $localSommeil;

    #[ORM\Column(name: 'PERIODICITE_PERIODICITE', type: Types::INTEGER, nullable: false, options: ['unsigned' => true])]
    private ?int $periodicite = null;

    #[ORM\Id]
    #[ORM\Column(name: 'ID_CATEGORIE', type: Types::INTEGER, nullable: false, options: ['unsigned' => true])]
    private int $categorie;

    #[ORM\Id]
    #[ORM\Column(name: 'ID_TYPE', type: Types::STRING, length: 10, nullable: false)]
    private string $type;

    public function getLocalSommeil(): bool
    {
        return $this->localSommeil;
    }

    public function setLocalSommeil(bool $localSommeil): self
    {
        $this->localSommeil = $localSommeil;

        return $this;
    }

    public function getPeriodicite(): ?int
    {
        return $this->periodicite;
    }

    public function setPeriodicite(int $periodicite): self
    {
        $this->periodicite = $periodicite;

        return $this;
    }

    public function getCategorie(): int
    {
        return $this->categorie;
    }

    public function setCategorie(int $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }
}
