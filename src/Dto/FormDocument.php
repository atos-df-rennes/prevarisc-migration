<?php

namespace App\Dto;

class FormDocument
{
    public function __construct(private readonly int $id, private readonly string $nom, private readonly ?string $type = null)
    {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function nom(): string
    {
        return $this->nom;
    }

    public function type(): ?string
    {
        return $this->type;
    }
}
