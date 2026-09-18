<?php

namespace App\Dto\FusionDesCommunes;

final readonly class FusionDeCommunes
{
    /**
     * @param Commune[] $listeCommunes
     */
    public function __construct(private string $nom, private string $numinsee, private array $listeCommunes)
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
