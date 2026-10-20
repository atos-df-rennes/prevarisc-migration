<?php

declare(strict_types=1);

namespace App\Tests\Story;

use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class TypePlanStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            1 => 'Plan ER',
            2 => 'PPI',
            3 => 'POI',
            4 => 'PPMS',
            5 => 'Plan de sauvegarde des oeuvres',
        ] as $id => $libelle) {
            $this->connection->insert('typeplan', ['ID_TYPEPLAN' => $id, 'LIBELLE_TYPEPLAN' => $libelle]);
        }
    }
}
