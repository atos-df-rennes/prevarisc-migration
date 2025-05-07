<?php

namespace App\Enum;

final class PlatauStatutPec
{
    public const INCONNU = 'unknown';
    public const EN_ATTENTE = 'awaiting';
    public const PRISE_EN_COMPTE = 'taken_into_account';
    public const A_RENVOYER = 'to_export';
    public const EN_ERREUR = 'in_error';

    public const LABELS = [
        self::INCONNU => 'Statut inconnu',
        self::EN_ATTENTE => 'En attente de complétude sur Prevarisc',
        self::PRISE_EN_COMPTE => "Prise en compte sur Plat'AU",
        self::A_RENVOYER => "Prise en compte sur Plat'AU (en attente de renvoi)",
        self::EN_ERREUR => "En erreur sur Plat'AU",
    ];

    public static function getLabel(?string $value): string
    {
        return self::LABELS[$value] ?? self::LABELS[self::INCONNU];
    }
    public static function getCssClass(?string $value): string
    {
        switch ($value) {
            case self::EN_ATTENTE:
                return 'text-info';
            case self::PRISE_EN_COMPTE:
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
