<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Issue de la suppression définitive d'un utilisateur (RG-USR-029).
 */
enum ResultatSuppressionUtilisateurEnum
{
    /** L'utilisateur n'existe plus ou n'appartient plus au groupe : rien n'a été supprimé. */
    case Absent;

    /** Le compte et sa fiche d'informations personnelles ont été supprimés. */
    case Supprime;

    /** Le compte a été supprimé ; sa fiche d'informations, partagée avec d'autres données, a été conservée intacte. */
    case SupprimeFicheConservee;
}
