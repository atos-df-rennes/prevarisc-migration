<?php

namespace App\Service\DataStore;

use App\Constant\DataStoreObjectTypes;
use App\Entity\PieceJointe;
use App\Repository\EtablissementRepository;

/**
 * Cette classe abstrait l'accès aux données du dépôt.
 */
class SimpleFileDataStore implements DataStore
{
    // Format de formattage des noms de fichiers.
    protected string $format = '%NOM_PIECEJOINTE%%EXTENSION_PIECEJOINTE%';

    // Mapping entre les types de pièces jointes et les répertoires dans lesquels ils sont stockés.
    /** @var array|string[] */
    protected $types = [];

    /**
     * @param array<string>|null $options
     */
    public function __construct(
        protected EtablissementRepository $etablissementRepository,
        protected string $dataPath,
        protected string $realDataPath,
        ?array $options = null
    ) {
        $this->types = [
            DataStoreObjectTypes::ETABLISSEMENT => 'pieces-jointes',
            DataStoreObjectTypes::ETABLISSEMENT_MINIATURE => 'etablissement_miniature',
            DataStoreObjectTypes::DOSSIER => 'pieces-jointes',
            DataStoreObjectTypes::DATE_COMMISSION => 'documents_commission',
            DataStoreObjectTypes::DOCUMENT => 'documents',
            DataStoreObjectTypes::COURRIER => 'courriers',
            DataStoreObjectTypes::AVATAR => 'avatars',
        ];

        if (isset($options['fileFormat'])) {
            $this->format = $options['fileFormat'];
        }
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
            '%DATE_PIECEJOINTE%' => $piece_jointe->getDate(),
            '%ID_OBJECT%' => $linkedObjectId,
            '%TYPE_OBJET%' => $linkedObjectType,
            '%CODE_TYPE_OBJET%' => strtoupper(substr($linkedObjectType, 0, 3)),
            '%SHORT_CODE_TYPE_OBJET%' => strtoupper(substr($linkedObjectType, 0, 1)),
            '%NUMEROID_ETABLISSEMENT%' => null,
        ];

        $etablissement = $this->etablissementRepository->find($linkedObjectId);
        // Logique pour déterminer le %NUMEROID_ETABLISSEMENT%
        switch ($linkedObjectType) {
            case DataStoreObjectTypes::ETABLISSEMENT:
                // Logique pour récupérer le numéro d'établissement
                $tokens[] = null !== $etablissement ? $etablissement->getId() : $linkedObjectId;
                break;
            case DataStoreObjectTypes::DOSSIER:
                // Logique pour récupérer le numéro d'établissement à partir du dossier
                $dossiers = null !== $etablissement ? $etablissement->getDossiers() : [];
                $default_numeroid = [];
                if ([] !== $dossiers) {
                    foreach ($dossiers as $dossier) {
                        $numero_id = $etablissement->getId() ?: $linkedObjectId;
                        if (false !== stripos($piece_jointe->getDescription() ?? '', (string) $numero_id)) {
                            $tokens['%NUMEROID_ETABLISSEMENT%'] = $numero_id;
                            break;
                        }

                        $default_numeroid[] = $numero_id;
                    }

                    $tokens['%NUMEROID_ETABLISSEMENT%'] ??= implode('_', $default_numeroid);
                } else {
                    $tokens['%NUMEROID_ETABLISSEMENT%'] = $linkedObjectId;
                }

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
