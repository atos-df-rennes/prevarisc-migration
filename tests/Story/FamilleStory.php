<?php

declare(strict_types=1);

namespace App\Tests\Story;

use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class FamilleStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            1 => 'Aucune',
            2 => '1ère famille',
            3 => '2ème famille',
            4 => '2ème famille collective',
            5 => '3ème famille A',
            6 => '3ème famille B',
            7 => '4ème famille',
            8 => 'Logements-foyers pour personne autres que personnes âgées et handicapés physique',
            9 => 'Logements-foyers pour personnes âgées',
            10 => 'Logements -foyers pour handicapés physiques ayant leur autonomie',
            11 => "3ème famille (classement antérieur à l'A. du 31/01/1986 modifié)",
        ] as $id => $libelle) {
            $this->connection->insert('famille', ['ID_FAMILLE' => $id, 'LIBELLE_FAMILLE' => $libelle]);
        }
    }
}
