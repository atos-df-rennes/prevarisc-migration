<?php

namespace App\Service\DataStore;

use App\Entity\PieceJointe;

/**
 * This class abstract the data access to the repository.
 */
interface DataStore
{
    public function getFilePath(PieceJointe $piece_jointe, string $linkedObjectType, int $linkedObjectId, bool $createDirIfNotExists = false): string;

    public function getURLPath(PieceJointe $piece_jointe, string $linkedObjectType, int $linkedObjectId): ?string;

    public function getFormattedFilename(PieceJointe $piece_jointe, string $linkedObjectType, int $linkedObjectId): ?string;
}
