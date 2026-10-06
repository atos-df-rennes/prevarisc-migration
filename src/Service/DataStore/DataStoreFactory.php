<?php

declare(strict_types=1);

namespace App\Service\DataStore;

use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;

class DataStoreFactory
{
    /** @var EtablissementRepository */
    private $etablissementRepository;

    /** @var DossierRepository */
    private $dossierRepository;

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
        DossierRepository $dossierRepository,
        string $adapter,
        string $dataPath,
        string $realDataPath,
        string $fileFormat
    ) {
        $this->etablissementRepository = $etablissementRepository;
        $this->dossierRepository = $dossierRepository;
        $this->adapter = $adapter;
        $this->dataPath = $dataPath;
        $this->realDataPath = $realDataPath;
        $this->fileFormat = $fileFormat;
    }

    public function create(): DataStore
    {
        if ('Plugin_HashedFileDataStore' === $this->adapter) {
            return new HashedFileDataStore($this->etablissementRepository, $this->dossierRepository, $this->dataPath, $this->realDataPath, $this->fileFormat);
        }

        return new SimpleFileDataStore($this->etablissementRepository, $this->dossierRepository, $this->dataPath, $this->realDataPath, $this->fileFormat);
    }
}
