<?php

declare(strict_types=1);

namespace App\Service\DataStore;

use App\Entity\PieceJointe;

class RuntimeDataStore implements DataStore
{
    /** @var DataStoreFactory */
    private $factory;

    /** @var DataStore|null */
    private $dataStore;

    public function __construct(DataStoreFactory $factory)
    {
        $this->factory = $factory;
    }

    private function getDataStore(): DataStore
    {
        if (null === $this->dataStore) {
            $this->dataStore = $this->factory->create();
        }

        return $this->dataStore;
    }

    public function getFilePath(PieceJointe $piece_jointe, string $linkedObjectType, int $linkedObjectId, bool $createDirIfNotExists = false): string
    {
        return $this->getDataStore()->getFilePath($piece_jointe, $linkedObjectType, $linkedObjectId, $createDirIfNotExists);
    }

    public function getURLPath(PieceJointe $piece_jointe, string $linkedObjectType, int $linkedObjectId): ?string
    {
        return $this->getDataStore()->getURLPath($piece_jointe, $linkedObjectType, $linkedObjectId);
    }

    public function getFormattedFilename(PieceJointe $piece_jointe, string $linkedObjectType, int $linkedObjectId): ?string
    {
        return $this->getDataStore()->getFormattedFilename($piece_jointe, $linkedObjectType, $linkedObjectId);
    }
}
