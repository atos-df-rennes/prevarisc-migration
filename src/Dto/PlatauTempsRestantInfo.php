<?php

namespace App\Dto;

/**
 * Value Object représentant les informations de temps restant pour une consultation Plat'AU.
 * Encapsule le temps restant et la couleur associée (Bootstrap label).
 */
final class PlatauTempsRestantInfo
{
    private ?string $tempsRestant;

    private ?string $couleur;

    public function __construct(?string $tempsRestant, ?string $couleur)
    {
        $this->tempsRestant = $tempsRestant;
        $this->couleur = $couleur;
    }

    /**
     * Factory method pour une info vide (pas de temps restant à afficher).
     */
    public static function empty(): self
    {
        return new self(null, null);
    }

    public function getTempsRestant(): ?string
    {
        return $this->tempsRestant;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    /**
     * Indique si les informations sont présentes (temps restant à afficher).
     */
    public function hasInfo(): bool
    {
        return null !== $this->tempsRestant;
    }
}
