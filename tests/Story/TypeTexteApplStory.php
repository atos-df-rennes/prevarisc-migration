<?php

declare(strict_types=1);

namespace App\Tests\Story;

use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class TypeTexteApplStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            1 => 'Dispositions générales',
            2 => 'Dispositions particulières',
            3 => 'Dispositions établissement de 5e catégorie',
            4 => 'Dispositions spéciales',
            5 => 'Textes relatifs aux immeubles de grande hauteur',
            6 => "Textes relatifs aux bâtiments d'habitation",
            7 => 'Textes relatifs aux bâtiments industriels',
            8 => 'Textes relatifs aux installations ouvertes au public',
            9 => 'Textes relatif aux terrains de camping',
        ] as $id => $libelle) {
            $this->connection->insert('typetextesappl', ['ID_TYPETEXTEAPPL' => $id, 'LIBELLE_TYPETEXTEAPPL' => $libelle]);
        }
    }
}
