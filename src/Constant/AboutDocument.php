<?php

namespace App\Constant;

/**
 * Constantes pour les documents d'aide.
 */
final class AboutDocument
{
    public const ABOUT = 'about';

    public const DEVELOPERS = 'dev';

    public const SUPPORT = 'support';

    public const TERMS_OF_SERVICE = 'tos';

    public const ALL = [
        self::ABOUT,
        self::DEVELOPERS,
        self::SUPPORT,
        self::TERMS_OF_SERVICE,
    ];

    private function __construct()
    {
    }
}
