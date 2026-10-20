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
            [50, 'avis_commission', "Gestion de l'avis de la commission"],
            [51, 'statut_etablissement', "Gestion du statut d'un établissement"],
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
