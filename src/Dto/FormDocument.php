<?php

namespace App\Dto;

class FormDocument
{
    /** @var int */
    private $id;

    /** @var string */
    private $nom;

    /** @var ?string */
    private $type;

    public function __construct(int $id, string $nom, ?string $type = null)
    {
        $this->id = $id;
        $this->nom = $nom;
        $this->type = $type;
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
