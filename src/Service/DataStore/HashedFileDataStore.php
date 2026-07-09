<?php

namespace App\Service\DataStore;

use App\Constant\DataStoreObjectTypes;
use App\Entity\PieceJointe;
use App\Repository\EtablissementRepository;

/**
 * This class abstract the data access to the repository.
 */
class HashedFileDataStore extends SimpleFileDataStore
{
    /** @var string[] */
    protected $nonHashedTypes = DataStoreObjectTypes::NON_HASHED_TYPES;

    /**
     * @param array<string>|null $options
     */
    public function __construct(
        EtablissementRepository $etablissementRepository,
        string $dataPath,
        string $realDataPath,
        ?array $options = null
    ) {
        parent::__construct($etablissementRepository, $dataPath, $realDataPath, $options);

        $this->types = [DataStoreObjectTypes::DATE_COMMISSION => 'commission'];

        $this->format = '%NOM_PIECEJOINTE%%EXTENSION_PIECEJOINTE%';
    }

    public function getBasePath(PieceJointe $piece_jointe, string $linkedObjectType, int $linkedObjectId): string
    {
        if (in_array($linkedObjectType, $this->nonHashedTypes)) {
            return parent::getBasePath($piece_jointe, $linkedObjectType, $linkedObjectId);
        }

        $type = $this->types[$linkedObjectType] ?? $linkedObjectType;

        $hash = md5((string) $piece_jointe->getId());

        return implode(DIRECTORY_SEPARATOR, [$this->realDataPath, 'uploads', $type, substr($hash, 0, 2), substr($hash, 2, 2), $linkedObjectId]);
    }

    public function getURLPath(PieceJointe $piece_jointe, string $linkedObjectType, int $linkedObjectId): ?string
    {
        $type = $this->types[$linkedObjectType] ?? $linkedObjectType;

        $hash = md5((string) $piece_jointe->getId());

        return implode(DIRECTORY_SEPARATOR, [$this->dataPath, 'uploads', $type, substr($hash, 0, 2), substr($hash, 2, 2), $linkedObjectId, $piece_jointe->getId().$piece_jointe->getExtension()]);
    }
}
