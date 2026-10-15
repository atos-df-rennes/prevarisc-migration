<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Références métier empêchant la suppression définitive d'un utilisateur (RG-USR-028).
 */
enum MotifConservationUtilisateurEnum: string
{
    case CreateurDossier = 'createur_dossier';

    case PreventionnisteDossier = 'preventionniste_dossier';

    case PreventionnisteEtablissement = 'preventionniste_etablissement';

    case PreventionnisteGroupement = 'preventionniste_groupement';

    case AuteurHistoriqueEtablissement = 'auteur_historique_etablissement';

    case AuteurSuppressionDossier = 'auteur_suppression_dossier';

    case AuteurSuppressionEtablissement = 'auteur_suppression_etablissement';

    case AuteurActualite = 'auteur_actualite';

    public function libelle(): string
    {
        return match ($this) {
            self::CreateurDossier => 'créateur de dossier',
            self::PreventionnisteDossier => 'préventionniste de dossier',
            self::PreventionnisteEtablissement => "préventionniste d'établissement",
            self::PreventionnisteGroupement => 'préventionniste de groupement',
            self::AuteurHistoriqueEtablissement => "auteur d'historique d'établissement",
            self::AuteurSuppressionDossier => 'auteur de suppression de dossier',
            self::AuteurSuppressionEtablissement => "auteur de suppression d'établissement",
            self::AuteurActualite => "auteur d'actualité",
        };
    }
}
