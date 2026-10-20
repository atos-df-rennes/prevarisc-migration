<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Constant\DossierType as DossierTypeId;
use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class DossierTypeStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            DossierTypeId::ETUDE => 'Étude',
            2 => 'Visite de commission',
            DossierTypeId::GROUPE_DE_VISITE => 'Groupe de visite',
            4 => 'Réunion',
            DossierTypeId::COURRIER => 'Courrier / Courriel',
            6 => 'Intervention',
            7 => 'Arrêté',
        ] as $id => $libelle) {
            $this->connection->insert('dossiertype', ['ID_DOSSIERTYPE' => $id, 'LIBELLE_DOSSIERTYPE' => $libelle]);
        }
    }
}
