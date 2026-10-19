<?php

namespace App\Enum;

final class PlatauStatutAvisEnum
{
    public const array LABELS = [
        self::INCONNU => 'Statut inconnu',
        self::EN_COURS => "En attente d'avis sur Prevarisc",
        self::SAISI_A_TRANSMETTRE => "Avis renseigné sur Prevarisc (en attente de transmission à Plat'AU)",
        self::TRAITE => "Traité sur Plat'AU",
        self::A_RENVOYER => "Traité sur Plat'AU (en attente de renvoi)",
        self::EN_ERREUR => "En erreur sur Plat'AU",
    ];

    public const string INCONNU = 'unknown';

    public const string EN_COURS = 'in_progress';

    // Statut d'affichage uniquement, jamais persiste.
    public const string SAISI_A_TRANSMETTRE = 'filled_awaiting_export';

    public const string TRAITE = 'treated';

    public const string A_RENVOYER = 'to_export';

    public const string EN_ERREUR = 'in_error';

    public static function getLabel(?string $enumValue): string
    {
        $enumValue ??= self::INCONNU;

        return self::LABELS[$enumValue] ?? self::LABELS[self::INCONNU];
    }
}
