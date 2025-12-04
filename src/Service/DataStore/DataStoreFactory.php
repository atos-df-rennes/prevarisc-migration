<?php

declare(strict_types=1);

namespace App\Service\DataStore;

use App\Repository\EtablissementRepository;

class DataStoreFactory
{
    /** @var EtablissementRepository */
    private $etablissementRepository;

    /** @var string */
    private $adapter;

    /** @var string */
    protected $dataPath;

    /** @var string */
    protected $realDataPath;

    public function __construct(
        EtablissementRepository $etablissementRepository,
        string $adapter,
        string $dataPath,
        string $realDataPath
    )
    {
        $this->etablissementRepository = $etablissementRepository;
        $this->adapter = $adapter;
        $this->dataPath = $dataPath;
        $this->realDataPath = $realDataPath;
    }

    public function create(): DataStore
    {
        if ('Plugin_HashedFileDataStore' === $this->adapter) {
            return new HashedFileDataStore($this->etablissementRepository, $this->dataPath, $this->realDataPath);
        }

        return new SimpleFileDataStore($this->etablissementRepository, $this->dataPath, $this->realDataPath);
    }
}
