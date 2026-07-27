<?php

namespace App\Dto\Formulaires;

final class NomFusion
{
    /**
     * @param self[] $enfants
     */
    public function __construct(private string $valeur, private array $enfants = [])
    {
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
