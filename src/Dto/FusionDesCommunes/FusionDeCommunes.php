<?php

namespace App\Dto\FusionDesCommunes;

final class FusionDeCommunes
{
    /**
     * @param Commune[] $listeCommunes
     */
    public function __construct(private readonly string $nom, private readonly string $numinsee, private readonly array $listeCommunes)
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

    /**
     * @return Commune[]
     */
    public function listeCommunes(): array
    {
        return $this->listeCommunes;
    }
}
