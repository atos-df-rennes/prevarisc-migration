<?php

namespace App\Dto\FusionDesCommunes;

final class Commune
{
    /** @var string */
    private $nom;

    /** @var string */
    private $numinsee;

    public function __construct(string $nom, string $numinsee)
    {
        $this->nom = $nom;
        $this->numinsee = $numinsee;
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
