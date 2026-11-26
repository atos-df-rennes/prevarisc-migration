<?php

declare(strict_types=1);

namespace App\Platau\Dto;

use App\Platau\ReceptionException;

final readonly class Piece
{
    public ?string $dtDepot;

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
        ?string $dtDepot = null,
        public ?string $txFileExtension = null,
    ) {
        if ('' === trim($idPiece) || '' === trim($noPiece) || $noVersion < 0 || '' === trim($txFileName)
            || preg_match('~[/\\\\\x00-\x1f\x7f]~', $txFileName)) {
            throw new ReceptionException('Identification de pièce incorrecte.');
        }

        $date = null;
        if (null !== $dtDepot) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}(?:T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})?)?$/D', $dtDepot)) {
                throw new ReceptionException('Date de dépôt incorrecte.');
            }
            try {
                $date = new \DateTimeImmutable($dtDepot);
            } catch (\Exception) {
                throw new ReceptionException('Date de dépôt incorrecte.');
            }
            $errors = \DateTimeImmutable::getLastErrors();
            if (false !== $errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                throw new ReceptionException('Date de dépôt incorrecte.');
            }
        }

        $this->dtDepot = $date?->format('Y-m-d') ?? null;
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
