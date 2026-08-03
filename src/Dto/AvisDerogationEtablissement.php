<?php

namespace App\Dto;

final readonly class AvisDerogationEtablissement
{
    public function __construct(private int $idDossier, private int $id, private string $titre, private string $type, private bool $leve, private ?string $debut, private ?int $typeDossier)
    {
    }

    public function idDossier(): int
    {
        return $this->idDossier;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function debut(): ?string
    {
        return $this->debut;
    }

    public function typeDossier(): ?int
    {
        return $this->typeDossier;
    }

    public function titre(): string
    {
        return $this->titre;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function leve(): bool
    {
        return $this->leve;
    }
}
