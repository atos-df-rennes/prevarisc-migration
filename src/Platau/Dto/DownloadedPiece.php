<?php

declare(strict_types=1);

namespace App\Platau\Dto;

use App\Platau\ReceptionException;

final readonly class DownloadedPiece
{
    /** @param resource $stream Fichier temporaire réutilisé pour chaque rattachement. */
    public function __construct(public string $extension, private mixed $stream, private int $size)
    {
    }

    /** @param resource $destination */
    public function copyTo(mixed $destination): void
    {
        if (!rewind($this->stream) || stream_copy_to_stream($this->stream, $destination) !== $this->size || !fflush($destination)) {
            throw new ReceptionException('Écriture de la pièce incomplète.');
        }
    }
}
