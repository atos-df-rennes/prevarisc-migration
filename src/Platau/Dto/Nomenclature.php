<?php

declare(strict_types=1);

namespace App\Platau\Dto;

final readonly class Nomenclature
{
    public ?int $idNom;

    public function __construct(
        mixed $idNom = null,
        public ?string $libNom = null,
    ) {
        // Une chaîne numérique ne désigne ni un rôle ni une nature connus.
        $this->idNom = is_int($idNom) ? $idNom : null;
    }
}
