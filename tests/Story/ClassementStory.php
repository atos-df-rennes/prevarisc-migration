<?php

declare(strict_types=1);

namespace App\Tests\Story;

use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class ClassementStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            1 => 'Artisanale',
            2 => 'Commerciale',
            3 => 'Industrielle',
            4 => 'Lotissement',
            5 => 'Autre',
        ] as $id => $libelle) {
            $this->connection->insert('classement', ['ID_CLASSEMENT' => $id, 'LIBELLE_CLASSEMENT' => $libelle]);
        }
    }
}
