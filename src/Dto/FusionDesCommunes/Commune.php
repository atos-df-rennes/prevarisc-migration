<?php

namespace App\Dto\FusionDesCommunes;

final class Commune
{
    public function __construct(private readonly string $nom, private readonly string $numinsee)
    {
    }

    public function nom(): string
    {
        return $this->nom;
    }

    public function numinsee(): string
    {
        return $this->numinsee;
    }
}
