<?php

namespace App\Enum;

final class PlatauStatutAvis
{
    public const INCONNU = 'unknown';
    public const EN_COURS = 'in_progress';
    public const TRAITE = 'treated';
    public const A_RENVOYER = 'to_export';
    public const EN_ERREUR = 'in_error';

    public const LABELS = [
        self::INCONNU => 'Statut inconnu',
        self::EN_COURS => 'En attente de complétude sur Prevarisc',
        self::TRAITE => "Traité sur Plat'AU",
        self::A_RENVOYER => "Traité sur Plat'AU (en attente de renvoi)",
        self::EN_ERREUR => "En erreur sur Plat'AU",
    ];

    public static function getLabel(?string $value): string
    {
        return self::LABELS[$value] ?? self::LABELS[self::INCONNU];
    }
    public static function getCssClass(?string $value): string
    {
        switch ($value) {
            case self::EN_COURS:
                return 'text-info';
            case self::TRAITE:
                return 'text-success';
            case self::A_RENVOYER:
                return 'text-warning';
            case self::EN_ERREUR:
                return 'text-danger';
            default:
                return 'text-muted';
        }
    }
    
}
