<?php

declare(strict_types=1);

namespace App\Tests\Story;

use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class GroupementTypeStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            1 => 'Département',
            2 => 'Arrondissement',
            3 => 'Canton',
            4 => 'Intercommunalité',
            5 => 'Groupement territorial',
            6 => 'Centre de secours',
            7 => 'Secrétariat prévention SDIS',
            8 => 'Secteur de prévention',
            9 => 'Secrétariat prévision',
            10 => 'Service instructeur',
            11 => 'DDSP',
            12 => 'Gendarmerie',
            13 => 'Autre service',
        ] as $id => $libelle) {
            $this->connection->insert('groupementtype', ['ID_GROUPEMENTTYPE' => $id, 'LIBELLE_GROUPEMENTTYPE' => $libelle]);
        }
    }
}
