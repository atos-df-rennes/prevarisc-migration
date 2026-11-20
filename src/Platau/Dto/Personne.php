<?php

declare(strict_types=1);

namespace App\Platau\Dto;

final readonly class Personne
{
    /**
     * @param Role[]|null   $roles
     * @param string[]|null $noms
     * @param string[]|null $prenoms
     */
    public function __construct(
        private ?array $roles = null,
        private ?array $noms = null,
        private ?array $prenoms = null,
        private ?string $libDenomination = null,
        private ?string $libRaisonSociale = null,
    ) {
    }

    public function estPetitionnaire(): bool
    {
        return array_any($this->roles ?? [], static fn (Role $role): bool => 1 === $role->nomRole?->idNom);
    }

    public function nomComplet(): string
    {
        if (null === $this->noms && null === $this->prenoms) {
            return $this->libDenomination ?? $this->libRaisonSociale ?? 'Inconnu';
        }

        return implode(' ', array_map(trim(...), [...($this->noms ?? []), ...($this->prenoms ?? [])]));
    }
}
