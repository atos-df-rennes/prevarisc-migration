<?php

namespace App\Constant;

/**
 * Constantes pour les documents d'aide.
 */
final class AboutDocument
{
    public const string ABOUT = 'about';

    public const string DEVELOPERS = 'dev';

    public const string SUPPORT = 'support';

    public const string TERMS_OF_SERVICE = 'tos';

    public const array ALL = [
        self::ABOUT,
        self::DEVELOPERS,
        self::SUPPORT,
        self::TERMS_OF_SERVICE,
    ];

    private function __construct()
    {
    }
}
