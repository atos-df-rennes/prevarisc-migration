<?php

namespace App\Constant;

/**
 * Énumère les types d'objets acceptés par les méthodes getFilePath(), getBasePath() et getURLPath()
 * des implémentations DataStore (SimpleFileDataStore, HashedFileDataStore).
 */
final class DataStoreObjectTypes
{
    /**
     * Type pour les établissements.
     */
    public const string ETABLISSEMENT = 'etablissement';

    /**
     * Type pour les miniatures d'établissements.
     */
    public const string ETABLISSEMENT_MINIATURE = 'etablissement_miniature';

    /**
     * Type pour les dossiers.
     */
    public const string DOSSIER = 'dossier';

    /**
     * Type pour les dates de commission.
     */
    public const string DATE_COMMISSION = 'dateCommission';

    /**
     * Type pour les documents.
     */
    public const string DOCUMENT = 'document';

    /**
     * Type pour les courriers.
     */
    public const string COURRIER = 'courrier';

    /**
     * Type pour les avatars.
     */
    public const string AVATAR = 'avatar';

    /**
     * Types qui ne doivent pas être hashés (utilisés par HashedFileDataStore).
     *
     * @var string[]
     */
    public const array NON_HASHED_TYPES = [
        self::DOCUMENT,
        self::COURRIER,
        self::AVATAR,
    ];

    /**
     * Tous les types disponibles.
     *
     * @var string[]
     */
    public const array ALL_TYPES = [
        self::ETABLISSEMENT,
        self::ETABLISSEMENT_MINIATURE,
        self::DOSSIER,
        self::DATE_COMMISSION,
        self::DOCUMENT,
        self::COURRIER,
        self::AVATAR,
    ];

    /**
     * Cette classe ne doit pas être instanciée.
     */
    private function __construct()
    {
    }
}
