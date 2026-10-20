<?php

declare(strict_types=1);

namespace App\Tests\Story;

use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class ClasseStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            1 => 'GHA',
            2 => 'GHO',
            3 => 'GHR',
            4 => 'GHS',
            5 => 'GHTC',
            6 => 'GHU',
            7 => 'GHW1',
            8 => 'GHW2',
            9 => 'GHZ',
            10 => 'ITGH',
        ] as $id => $libelle) {
            $this->connection->insert('classe', ['ID_CLASSE' => $id, 'LIBELLE_CLASSE' => $libelle]);
        }
    }
}
