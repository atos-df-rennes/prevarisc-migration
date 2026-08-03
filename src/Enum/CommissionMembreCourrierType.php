<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Types de courriers disponibles pour un membre de commission.
 * PV est uniquement disponible pour les membres sans groupement.
 */
final class CommissionMembreCourrierType
{
    public const string CONVOCATIONVISITE = 'CONVOCATIONVISITE';

    public const string CONVOCATIONSALLE = 'CONVOCATIONSALLE';

    public const string ODJ = 'ODJ';

    public const string PV = 'PV';

    /** @var string[] */
    public const array ALL = [
        self::CONVOCATIONVISITE,
        self::CONVOCATIONSALLE,
        self::ODJ,
        self::PV,
    ];

    public static function isValid(string $type): bool
    {
        return \in_array($type, self::ALL, true);
    }
}
