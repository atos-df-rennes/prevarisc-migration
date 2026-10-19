<?php

namespace App\Dto;

final readonly class FormDocument
{
    public function __construct(private int $id, private string $nom, private ?string $type = null)
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
