<?php

namespace App\Dto\Formulaires;

final class NomFusion
{
    private string $valeur;

    /** @var self[] */
    private array $enfants;

    /**
     * @param self[] $enfants
     */
    public function __construct(string $valeur, array $enfants = [])
    {
        $this->valeur = $valeur;
        $this->enfants = $enfants;
    }

    public function valeur(): string
    {
        return $this->valeur;
    }

    /**
     * @return NomFusion[]
     */
    public function enfants(): array
    {
        return $this->enfants;
    }
}
