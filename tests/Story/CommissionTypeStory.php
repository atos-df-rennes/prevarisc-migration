<?php

declare(strict_types=1);

namespace App\Tests\Story;

use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class CommissionTypeStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            1 => 'Sous-commission départementale',
            2 => 'Commission communale',
            3 => 'Commission intercommunale',
            4 => "Commission d'arrondissement",
            5 => 'Divers',
        ] as $id => $libelle) {
            $this->connection->insert('commissiontype', ['ID_COMMISSIONTYPE' => $id, 'LIBELLE_COMMISSIONTYPE' => $libelle]);
        }
    }
}
