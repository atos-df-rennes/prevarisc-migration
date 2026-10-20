<?php

declare(strict_types=1);

namespace App\Tests\Story;

use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class TypeStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            1 => 'CTS',
            2 => 'EF',
            3 => 'EM',
            4 => 'EP',
            5 => 'GA',
            6 => 'GEEM',
            7 => 'J',
            8 => 'L',
            9 => 'M',
            10 => 'N',
            11 => 'O',
            12 => 'OA',
            13 => 'P',
            14 => 'PA',
            15 => 'PE2§2',
            16 => 'PS',
            17 => 'R',
            18 => 'REF',
            19 => 'S',
            20 => 'SG',
            21 => 'T',
            22 => 'U',
            23 => 'V',
            24 => 'W',
            25 => 'X',
            26 => 'Y',
            27 => 'Z',
        ] as $id => $libelle) {
            $this->connection->insert('type', ['ID_TYPE' => $id, 'LIBELLE_TYPE' => $libelle]);
        }
    }
}
