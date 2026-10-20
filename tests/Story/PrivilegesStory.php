<?php

declare(strict_types=1);

namespace App\Tests\Story;

use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class PrivilegesStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            [1, 'docs', 'Gestion des documents', 1],
            [2, 'groupement_communes', 'Gestion des groupements de communes', 1],
            [3, 'gestion_prescriptions', 'Gestion des prescriptions', 1],
            [4, 'gestion_textes_applicables', 'Gestion des textes applicables', 1],
            [5, 'fil_actus', "Écriture dans le fil d'actualités", 1],
            [6, 'gestion_commissions', 'Gestion des commissions', 1],
            [7, 'lecture_commission', 'Lecture', 2],
            [8, 'ecriture_commission', 'Écriture', 2],
            [9, 'creation_commission', 'Création', 2],
            [10, 'modification_odj', "Modification de l'Ordre du Jour", 2],
            [11, 'admin', "Accès à l'administration", 1],
            [12, 'communes', 'Gestion des communes', 1],
            [13, 'periodicites', 'Gestion des périodicités', 1],
            [14, 'add_etablissement', "Création d'un établissement", 3],
            [15, 'add_dossier', "Création d'un dossier", 3],
            [16, 'generation_doc_com', 'Génération des documents de la commission', 2],
            [17, 'view_ets', 'Lecture', 4],
            [18, 'edit_ets', 'Modifier', 4],
            [19, 'view_ets', 'Lecture', 5],
            [20, 'edit_ets', 'Modifier', 5],
            [21, 'view_ets', 'Lecture', 6],
            [22, 'edit_ets', 'Modifier', 6],
            [23, 'view_ets', 'Lecture', 7],
            [24, 'edit_ets', 'Modifier', 7],
            [25, 'view_ets', 'Lecture', 8],
            [26, 'edit_ets', 'Modifier', 8],
            [27, 'view_doss', 'Lecture', 9],
            [28, 'edit_doss', 'Modifier', 9],
            [29, 'verrouillage_dossier', "Verrouillage d'un dossier", 9],
            [50, 'edit_avis_com', 'Modifier', 50],
            [51, 'edit_statut', 'Modifier', 51],
            [52, 'view_ets', 'Lecture', 52],
            [53, 'edit_ets', 'Modifier', 52],
            [54, 'view_ets', 'Lecture', 53],
            [55, 'edit_ets', 'Modifier', 53],
            [56, 'view_ets', 'Lecture', 54],
            [57, 'edit_ets', 'Modifier', 54],
            [58, 'view_ets', 'Lecture', 55],
            [59, 'edit_ets', 'Modifier', 55],
            [100, 'view_ets_avis_defavorable', 'Voir les établissements sous avis défavorable', 100],
            [101, 'view_doss_sans_avis', 'Voir dossiers de commissions échus sans avis', 100],
            [102, 'view_ets_ouverts_sans_prochaine_vp', 'Voir ets sans prochaine visite périodique', 100],
            [103, 'view_courrier_sans_reponse', 'Voir les courriers sans réponse', 100],
            [104, 'view_ets_sans_preventionniste', 'Voir ets sans préventionniste', 100],
            [105, 'view_doss_avis_differe', 'Voir dossiers avec avis différés', 100],
            [106, 'view_ets_avis_defavorable_sur_commune', 'Voir ets défavorable sur commune utilisateur', 100],
            [107, 'view_ets_suivis', 'Voir ets suivis', 100],
            [108, 'view_doss_suivis_unlocked', 'Voir les dossiers suivis déverrouillés', 100],
            [109, 'view_doss_suivis_sans_avis', 'Voir les dossiers suivis sans avis', 100],
            [110, 'view_ets_avis_defavorable_suivis', 'Voir les établissements défavorables suivis', 100],
            [111, 'view_next_commissions', 'Voir les prochaines commissions', 100],
            [112, 'view_next_commissions_odj', 'Voir les odj des prochaines commissions', 100],
            [113, 'view_doss_levee_prescriptions', 'Date de levée des prescriptions', 100],
            [114, 'view_doss_absence_quorum', 'Dossier avec absence de quorum', 100],
            [115, 'view_doss_npsp', 'Dossiers avec statut ne peut se prononcer', 100],
            [60, 'alerte_statut', 'Changement de statut', 56],
            [61, 'alerte_avis', "Changement d'avis", 56],
            [62, 'alerte_classement', 'Changement de classement', 56],
            [63, 'search_ets', 'Etablissements', 57],
            [64, 'search_dossiers', 'Dossiers', 57],
            [70, 'delete_dossier', 'Suppression des dossiers', 60],
            [71, 'delete_etablissement', 'Suppression des établissements', 60],
            [80, 'calendar_view_all', 'Voir tous les dossiers et commissions', 2],
            [200, 'export_ets', 'Etablissements', 200],
            [210, 'export_doss', 'Dossiers', 200],
            [211, 'view_doss_platau_sans_etab', "Dossiers Plat'AU à traiter", 100],
            [212, 'gestion_formulaires', 'Gestion des formulaires personnalisés', 1],
            [213, 'lecture_verifications_techniques', 'Lecture', 201],
            [214, 'lecture_avis_derogations', 'Lecture', 202],
            [215, 'effectifs_degagements_ets', 'Etablissements', 203],
            [216, 'effectifs_degagements_doss', 'Dossiers', 203],
            [217, 'view_doss_platau_pjs_en_erreur', "Dossiers Plat'AU pièces jointes non envoyées", 100],
            [218, 'view_doss_platau_nouvelles_pjs', "Dossiers Plat'AU nouvelles pièces jointes", 100],
            [219, 'view_doss_incomplets', 'Dossiers incomplets', 100],
            [220, 'ecriture_avis_derogations', 'Écriture', 202],
            [221, 'ecriture_verifications_techniques', 'Écriture', 201],
        ] as [$id, $name, $text, $resourceId]) {
            $this->connection->insert('privileges', [
                'id_privilege' => $id,
                'name' => $name,
                'text' => $text,
                'id_resource' => $resourceId,
            ]);
        }
    }
}
