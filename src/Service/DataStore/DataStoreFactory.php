<?php

declare(strict_types=1);

namespace App\Service\DataStore;

use App\Repository\EtablissementRepository;

class DataStoreFactory
{
    public function __construct(private EtablissementRepository $etablissementRepository, private string $adapter, protected string $dataPath, protected string $realDataPath)
    {
    }

    public function create(): DataStore
    {
        if ('Plugin_HashedFileDataStore' === $this->adapter) {
            return new HashedFileDataStore($this->etablissementRepository, $this->dataPath, $this->realDataPath);
        }

        return new SimpleFileDataStore($this->etablissementRepository, $this->dataPath, $this->realDataPath);
    }
}
