<?php

namespace App\Dto;

final class AvisDerogationEtablissement
{
    public function __construct(private readonly int $idDossier, private readonly int $id, private readonly string $titre, private readonly string $type, private readonly bool $leve, private readonly ?string $debut, private readonly ?int $typeDossier)
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
