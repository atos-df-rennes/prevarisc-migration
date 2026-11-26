<?php

declare(strict_types=1);

namespace App\Platau;

use App\Platau\Dto\Piece;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class PieceReception
{
    public function __construct(
        private HttpPieceReader $reader,
        private DenormalizerInterface $denormalizer,
        private PieceDownloader $downloader,
        private PieceStorage $storage,
    ) {
    }

    /** @return iterable<string> Diagnostics des seules pièces en échec. */
    public function initial(string $dossier, int $localId): iterable
    {
        foreach ($this->reader->pieces($dossier) as $index => $row) {
            try {
                $piece = $this->piece($row, $dossier);
                if (!$this->storage->exists($localId, $piece)) {
                    $this->storage->attach($localId, $piece, $this->downloader->download($piece));
                }
            } catch (\Throwable $exception) {
                yield sprintf('Échec de la pièce initiale n°%d : %s', $index + 1, $exception instanceof ReceptionException ? $exception->getMessage() : 'import impossible.');
            }
        }
    }

    public function notified(string $dossier, string $id): void
    {
        $piece = $this->notifiedPiece($dossier, $id);
        $targets = [];
        foreach ($this->reader->consultationIds($dossier) as $consultation) {
            $localId = $this->storage->dossierId($consultation);
            if (null !== $localId && !$this->storage->exists($localId, $piece)) {
                $targets[$localId] = $localId;
            }
        }
        if ([] === $targets) {
            return;
        }

        $download = $this->downloader->download($piece);
        $failures = 0;
        foreach ($targets as $localId) {
            try {
                $this->storage->attach($localId, $piece, $download);
            } catch (\Throwable) {
                ++$failures;
            }
        }
        if ($failures > 0) {
            throw new ReceptionException(sprintf('Échec du stockage pour %d dossier(s).', $failures));
        }
    }

    private function notifiedPiece(string $dossier, string $id): Piece
    {
        foreach ($this->reader->pieces($dossier) as $row) {
            if (is_array($row) && ($row['idPiece'] ?? null) === $id) {
                return $this->piece($row, $dossier);
            }
        }

        throw new ReceptionException('La pièce notifiée est introuvable dans le dossier.');
    }

    private function piece(#[\SensitiveParameter] mixed $row, string $dossier): Piece
    {
        if (!is_array($row) || !empty($row['erreur']) || ($row['idDossier'] ?? $dossier) !== $dossier) {
            throw new ReceptionException('Pièce incorrecte ou étrangère au dossier.');
        }
        try {
            return $this->denormalizer->denormalize($row, Piece::class);
        } catch (\Throwable) {
            throw new ReceptionException('Métadonnées de pièce incorrectes.');
        }
    }
}
