<?php

declare(strict_types=1);

namespace App\Service\DataStore;

use App\Repository\EtablissementRepository;

class DataStoreFactory
{
    public function __construct(private readonly EtablissementRepository $etablissementRepository, private readonly string $adapter, protected string $dataPath, protected string $realDataPath, private readonly string $fileFormat)
    {
    }

    public function create(): DataStore
    {
        if ('Plugin_HashedFileDataStore' === $this->adapter) {
            return new HashedFileDataStore($this->etablissementRepository, $this->dataPath, $this->realDataPath, $this->fileFormat);
        }

        return new SimpleFileDataStore($this->etablissementRepository, $this->dataPath, $this->realDataPath, $this->fileFormat);
    }
}
