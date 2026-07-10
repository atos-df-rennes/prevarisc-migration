<?php

namespace App\Dto\FusionDesCommunes;

final class FusionDeCommunes
{
    private string $nom;

    private string $numinsee;

    /** @var Commune[] */
    private array $listeCommunes;

    /**
     * @param Commune[] $listeCommunes
     */
    public function __construct(string $nom, string $numinsee, array $listeCommunes)
    {
        $this->nom = $nom;
        $this->numinsee = $numinsee;
        $this->listeCommunes = $listeCommunes;
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
