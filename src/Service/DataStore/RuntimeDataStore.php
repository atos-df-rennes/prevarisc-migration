<?php

declare(strict_types=1);

namespace App\Service\DataStore;

use App\Entity\PieceJointe;

/**
 * Proxy/wrapper for lazy-loading the actual DataStore implementation at runtime.
 *
 * This class uses a DataStoreFactory to instantiate the underlying DataStore only when needed,
 * following the lazy initialization pattern. It delegates all DataStore interface methods to
 * the real DataStore instance, which is created on first use.
 *
 * This architectural pattern helps to defer resource-intensive initialization and allows
 * for flexible runtime selection of the DataStore implementation.
 */
class RuntimeDataStore implements DataStore
{
    private ?DataStore $dataStore = null;

    public function __construct(private readonly DataStoreFactory $factory)
    {
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
