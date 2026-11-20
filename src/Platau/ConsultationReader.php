<?php

declare(strict_types=1);

namespace App\Platau;

interface ConsultationReader
{
    /** @return array<array-key, mixed> Élément brut de resultats, dossier compris. */
    public function details(string $id): array;
}
