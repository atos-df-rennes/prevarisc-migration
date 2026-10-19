<?php

namespace App\Service\DataStore;

use App\Constant\DataStoreObjectTypes;
use App\Entity\PieceJointe;
use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;

/**
 * This class abstract the data access to the repository.
 */
final class HashedFileDataStore extends SimpleFileDataStore
{
    /** @var string[] */
    protected $nonHashedTypes = DataStoreObjectTypes::NON_HASHED_TYPES;

    public function __construct(
        EtablissementRepository $etablissementRepository,
        DossierRepository $dossierRepository,
        string $dataPath,
        string $realDataPath,
        string $fileFormat
    ) {
        parent::__construct($etablissementRepository, $dossierRepository, $dataPath, $realDataPath, $fileFormat);

        $this->types = [DataStoreObjectTypes::DATE_COMMISSION => 'commission'];
    }

    #[\Override]
    public function getBasePath(PieceJointe $piece_jointe, string $linkedObjectType, int $linkedObjectId): string
    {
        if (in_array($linkedObjectType, $this->nonHashedTypes)) {
            return parent::getBasePath($piece_jointe, $linkedObjectType, $linkedObjectId);
        }

        $type = $this->types[$linkedObjectType] ?? $linkedObjectType;

        $hash = md5((string) $piece_jointe->getId());

        return implode(DIRECTORY_SEPARATOR, [$this->realDataPath, 'uploads', $type, substr($hash, 0, 2), substr($hash, 2, 2), $linkedObjectId]);
    }

    #[\Override]
    public function getURLPath(PieceJointe $piece_jointe, string $linkedObjectType, int $linkedObjectId): ?string
    {
        $type = $this->types[$linkedObjectType] ?? $linkedObjectType;

        $hash = md5((string) $piece_jointe->getId());

        return implode(DIRECTORY_SEPARATOR, [$this->dataPath, 'uploads', $type, substr($hash, 0, 2), substr($hash, 2, 2), $linkedObjectId, $piece_jointe->getId().$piece_jointe->getExtension()]);
    }
}
