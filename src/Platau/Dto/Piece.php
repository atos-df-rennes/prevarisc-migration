<?php

declare(strict_types=1);

namespace App\Platau\Dto;

use App\Platau\ReceptionException;

final readonly class Piece
{
    public function __construct(
        public string $idPiece,
        public string $noPiece,
        public int $noVersion,
        public string $txFileName,
        #[\SensitiveParameter] public string $url,
        #[\SensitiveParameter] public string $token,
        public ?Nomenclature $nomTypePiece = null,
        public ?string $libAutreTypePiece = null,
        public ?Nomenclature $nomNaturePiece = null,
        public ?string $dtDepot = null,
        public ?string $txFileExtension = null,
    ) {
        if ('' === trim($idPiece) || '' === trim($noPiece) || $noVersion < 0 || '' === trim($txFileName)
            || preg_match('~[/\\\\\x00-\x1f\x7f]~', $txFileName)) {
            throw new ReceptionException('Identification de pièce incorrecte.');
        }

        if (null !== $dtDepot) {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $dtDepot);
            if (false === $date || $date->format('Y-m-d') !== $dtDepot) {
                throw new ReceptionException('Date de dépôt incorrecte.');
            }
        }
    }

    public function filename(): string
    {
        return sprintf('%s-v%d', $this->txFileName, $this->noVersion);
    }

    public function legacyFilename(): string
    {
        return sprintf('PLATAU-%s-%s-v%d', $this->idPiece, $this->noPiece, $this->noVersion);
    }
}
