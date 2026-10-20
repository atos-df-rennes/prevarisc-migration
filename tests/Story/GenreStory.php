<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Constant\GenreEtablissement;
use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class GenreStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            GenreEtablissement::SITE => 'Site',
            GenreEtablissement::ERP => 'Établissement',
            GenreEtablissement::CELLULE => 'Cellule',
            GenreEtablissement::HABITATION => 'Habitation',
            GenreEtablissement::IGH => 'IGH',
            GenreEtablissement::BUP => 'BUP',
            GenreEtablissement::CAMPING => 'Camping',
            GenreEtablissement::MANIFESTATION_TEMPORAIRE => 'Manifestation Temporaire',
            GenreEtablissement::IOP => 'IOP',
            GenreEtablissement::ZONE => 'Zone',
        ] as $id => $libelle) {
            $this->connection->insert('genre', ['ID_GENRE' => $id, 'LIBELLE_GENRE' => $libelle]);
        }
    }
}
