<?php

namespace App\Enum;

final class PlatauStatutPecEnum
{
    public const array LABELS = [
        self::INCONNU => 'Statut inconnu',
        self::EN_ATTENTE => 'En attente de complétude sur Prevarisc',
        self::SAISIE_A_TRANSMETTRE => "Complétude renseignée sur Prevarisc (en attente de transmission à Plat'AU)",
        self::PRISE_EN_COMPTE => "Prise en compte sur Plat'AU",
        self::A_RENVOYER => "Prise en compte sur Plat'AU (en attente de renvoi)",
        self::EN_ERREUR => "En erreur sur Plat'AU",
    ];

    public const string INCONNU = 'unknown';

    public const string EN_ATTENTE = 'awaiting';

    // Statut d'affichage uniquement, jamais persiste.
    public const string SAISIE_A_TRANSMETTRE = 'filled_awaiting_export';

    public const string PRISE_EN_COMPTE = 'taken_into_account';

    public const string A_RENVOYER = 'to_export';

    public const string EN_ERREUR = 'in_error';

    public static function getLabel(?string $enumValue): string
    {
        $enumValue ??= self::INCONNU;

        return self::LABELS[$enumValue] ?? self::LABELS[self::INCONNU];
    }
}
