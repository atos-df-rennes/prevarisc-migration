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

    /** @var string */
    private $fileFormat;

    public function __construct(
        EtablissementRepository $etablissementRepository,
        string $adapter,
        string $dataPath,
        string $realDataPath,
        string $fileFormat = ''
    ) {
        $this->etablissementRepository = $etablissementRepository;
        $this->adapter = $adapter;
        $this->dataPath = $dataPath;
        $this->realDataPath = $realDataPath;
        $this->fileFormat = $fileFormat;
    }

    public function create(): DataStore
    {
        $options = '' !== $this->fileFormat ? ['fileFormat' => $this->fileFormat] : null;

        if ('Plugin_HashedFileDataStore' === $this->adapter) {
            return new HashedFileDataStore($this->etablissementRepository, $this->dataPath, $this->realDataPath, $options);
        }

        return new SimpleFileDataStore($this->etablissementRepository, $this->dataPath, $this->realDataPath, $options);
    }
}
