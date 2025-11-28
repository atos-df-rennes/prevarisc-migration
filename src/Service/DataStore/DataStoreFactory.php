<?php

declare(strict_types=1);

namespace App\Service\DataStore;

use App\Repository\EtablissementRepository;

class DataStoreFactory
{
    /** @var EtablissementRepository */
    private $etablissementRepository;

    public function __construct(EtablissementRepository $etablissementRepository)
    {
        $this->etablissementRepository = $etablissementRepository;
    }

    public function create(): DataStore
    {
        $adapter = getenv('PREVARISC_DATASTORE_ADAPTER') ?: 'Plugin_SimpleFileDataStore';

        if ('Plugin_HashedFileDataStore' === $adapter) {
            return new HashedFileDataStore($this->etablissementRepository);
        }

        return new SimpleFileDataStore($this->etablissementRepository);
    }
}
