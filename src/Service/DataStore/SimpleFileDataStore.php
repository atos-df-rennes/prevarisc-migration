<?php

namespace App\Service\DataStore;

use App\Constant\DataStoreObjectTypes;
use App\Entity\PieceJointe;
use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;

/**
 * Cette classe abstrait l'accès aux données du dépôt.
 */
class SimpleFileDataStore implements DataStore
{
    // Format de formattage des noms de fichiers.
    protected string $format;

    // Mapping entre les types de pièces jointes et les répertoires dans lesquels ils sont stockés.
    /** @var array|string[] */
    protected $types = [];

    public function __construct(
        protected EtablissementRepository $etablissementRepository,
        protected DossierRepository $dossierRepository,
        protected string $dataPath,
        protected string $realDataPath,
        string $fileFormat
    ) {
        if ('' === trim($fileFormat)) {
            throw new \InvalidArgumentException('PREVARISC_DATASTORE_FILE_FORMAT must not be empty. Unset it to use the default filename format.');
        }

        $this->types = [
            DataStoreObjectTypes::ETABLISSEMENT => 'pieces-jointes',
            DataStoreObjectTypes::ETABLISSEMENT_MINIATURE => 'etablissement_miniature',
            DataStoreObjectTypes::DOSSIER => 'pieces-jointes',
            DataStoreObjectTypes::DATE_COMMISSION => 'documents_commission',
            DataStoreObjectTypes::DOCUMENT => 'documents',
            DataStoreObjectTypes::COURRIER => 'courriers',
            DataStoreObjectTypes::AVATAR => 'avatars',
        ];

        $this->format = $fileFormat;
    }

    public function getFilePath(PieceJointe $piece_jointe, string $linkedObjectType, int $linkedObjectId, bool $createDirIfNotExists = false): string
    {
        $directory = $this->getBasePath($piece_jointe, $linkedObjectType, $linkedObjectId);
        if ($createDirIfNotExists && !is_dir($directory) && !@mkdir($directory, 0777, true)) {
            $error = error_get_last();
            if (null !== $error) {
                throw new \Exception('Cannot create base directory '.$directory.': '.$error['message']);
            }

            throw new \Exception('Cannot create base directory '.$directory);
        }

        return implode(DIRECTORY_SEPARATOR, [
            $directory,
            $piece_jointe->getId().$piece_jointe->getExtension(),
        ]);
    }

    public function getURLPath(PieceJointe $piece_jointe, string $linkedObjectType, int $linkedObjectId): ?string
    {
        $type = $this->types[$linkedObjectType] ?? $linkedObjectType;

        return implode(DIRECTORY_SEPARATOR, [
            $this->dataPath, 'uploads', $type, $piece_jointe->getId().$piece_jointe->getExtension(),
        ]);
    }

    public function getFormattedFilename(PieceJointe $piece_jointe, string $linkedObjectType, int $linkedObjectId): ?string
    {
        $tokens = [
            '%ID_PIECEJOINTE%' => $piece_jointe->getId(),
            '%NOM_PIECEJOINTE%' => $piece_jointe->getNom(),
            '%EXTENSION_PIECEJOINTE%' => $piece_jointe->getExtension(),
            '%DESCRIPTION_PIECEJOINTE%' => $piece_jointe->getDescription(),
            '%DATE_PIECEJOINTE%' => $piece_jointe->getDate()?->format('Y-m-d') ?? '',
            '%ID_OBJECT%' => $linkedObjectId,
            '%TYPE_OBJET%' => $linkedObjectType,
            '%CODE_TYPE_OBJET%' => strtoupper(substr($linkedObjectType, 0, 3)),
            '%SHORT_CODE_TYPE_OBJET%' => strtoupper(substr($linkedObjectType, 0, 1)),
            '%NUMEROID_ETABLISSEMENT%' => null,
        ];

        switch ($linkedObjectType) {
            case DataStoreObjectTypes::ETABLISSEMENT:
                $etablissement = $this->etablissementRepository->find($linkedObjectId);
                $tokens['%NUMEROID_ETABLISSEMENT%'] = null !== $etablissement
                    ? ($etablissement->getNumero() ?: $etablissement->getId() ?: $linkedObjectId)
                    : $linkedObjectId;
                break;
            case DataStoreObjectTypes::DOSSIER:
                $dossier = $this->dossierRepository->find($linkedObjectId);
                $etablissements = null !== $dossier ? $dossier->getEtablissements() : [];
                $numerosEtablissement = [];
                foreach ($etablissements as $etablissement) {
                    $numeroId = $etablissement->getNumero() ?: $linkedObjectId;
                    $numerosEtablissement[] = (string) $numeroId;
                    if (false !== stripos($piece_jointe->getDescription() ?? '', (string) $numeroId)) {
                        $tokens['%NUMEROID_ETABLISSEMENT%'] = $numeroId;
                        break;
                    }
                }

                $tokens['%NUMEROID_ETABLISSEMENT%'] ??= [] !== $numerosEtablissement
                    ? implode('_', $numerosEtablissement)
                    : $linkedObjectId;

                break;
            default:
                $tokens['%NUMEROID_ETABLISSEMENT%'] = $linkedObjectId;
                break;
        }

        return strtr($this->format, $tokens);
    }

    // Méthode supplémentaire pour obtenir le chemin de base
    public function getBasePath(PieceJointe $piece_jointe, string $linkedObjectType, int $linkedObjectId): string
    {
        $type = $this->types[$linkedObjectType] ?? $linkedObjectType;

        return implode(DIRECTORY_SEPARATOR, [$this->realDataPath, 'uploads', $type]);
    }
}
