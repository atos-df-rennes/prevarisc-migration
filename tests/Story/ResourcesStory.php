<?php

declare(strict_types=1);

namespace App\Tests\Story;

use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class ResourcesStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            [1, 'gestion_parametrages', 'Gestion et Paramétrages'],
            [2, 'commission', 'Gestion des calendriers des commissions'],
            [3, 'creations', 'Gestion des droits de création'],
            [4, 'etablissement_erp_0_0_0_0_0', 'Établissement (Tous les types - Toutes les catégories - Ignorer les commissions - Ignorer les groupements - Ignorer la commune)'],
            [5, 'etablissement_cell_0_0', 'Cellule (Tous les types - Toutes les catégories)'],
            [6, 'etablissement_hab_0_0_0', 'Habitation (Toutes les familles - Ignorer les groupements - Ignorer la commune)'],
            [7, 'etablissement_igh_0_0_0_0', 'IGH (Toutes les classes - Ignorer les commissions - Ignorer les groupements - Ignorer la commune)'],
            [8, 'etablissement_bup_0_0', 'BUP (Ignorer les groupements - Ignorer la commune)'],
            [9, 'dossier_0', 'Dossier (Toutes les natures)'],
            [50, 'avis_commission', "Gestion de l'avis de la commission"],
            [51, 'statut_etablissement', "Gestion du statut d'un établissement"],
            [52, 'etablissement_camp_0_0', 'Camping (Ignorer les groupements - Ignorer la commune)'],
            [53, 'etablissement_temp_0_0', 'Manifestation temporaire (Ignorer les groupements - Ignorer la commune)'],
            [54, 'etablissement_iop_0_0', 'IOP (Ignorer les groupements - Ignorer la commune)'],
            [55, 'etablissement_zone_0_0_0', 'Zone (Toutes les classes - Ignorer les groupements - Ignorer la commune)'],
            [56, 'alerte_email', 'Alertes'],
            [57, 'search', 'Recherche'],
            [60, 'suppression', 'Suppression'],
            [100, 'dashboard', 'Tableau de bord'],
            [200, 'export', 'Export Calc'],
            [201, 'verificationstechniques', 'Vérifications techniques'],
            [202, 'avisderogations', 'Avis et dérogations'],
            [203, 'effectifs_degagements', 'Effectifs et Dégagements'],
        ] as [$id, $name, $text]) {
            $this->connection->insert('resources', [
                'id_resource' => $id,
                'name' => $name,
                'text' => $text,
            ]);
        }
    }
}
