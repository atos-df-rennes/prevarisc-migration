<?php

namespace App\Constant;

/**
 * Constantes pour les types d'événements de commission.
 */
final class CommissionTypeEvenement
{
    /** Commission en salle */
    public const TYPE_COMMISSION_SALLE = 1;

    /** Visite sur site */
    public const TYPE_VISITE = 2;

    /** Groupe de visites */
    public const TYPE_GROUPE_VISITE = 3;

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
