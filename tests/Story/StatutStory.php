<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Constant\StatutEtablissement;
use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class StatutStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            StatutEtablissement::PROJET => 'Projet',
            StatutEtablissement::OUVERT => 'Ouvert',
            StatutEtablissement::FERME => 'Fermé',
            StatutEtablissement::ITINERANT_PERIODIQUE => 'Itinérant / Périodique',
        ] as $id => $libelle) {
            $this->connection->insert('statut', ['ID_STATUT' => $id, 'LIBELLE_STATUT' => $libelle]);
        }
    }
}
