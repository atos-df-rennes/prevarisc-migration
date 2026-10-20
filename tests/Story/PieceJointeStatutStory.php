<?php

declare(strict_types=1);

namespace App\Tests\Story;

use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class PieceJointeStatutStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            [1, 'not_exported', 'Non envoyé'],
            [2, 'to_be_exported', "En attente d'envoi"],
            [3, 'exported', 'Envoyé'],
            [4, 'on_error', "Erreur lors de l'envoi"],
            [5, 'awaiting_status', 'Vérification du statut'],
        ] as [$id, $nomStatut, $nomLisible]) {
            $this->connection->insert('piecejointestatut', [
                'ID_PIECEJOINTESTATUT' => $id,
                'NOM_STATUT' => $nomStatut,
                'NOM_LISIBLE' => $nomLisible,
            ]);
        }
    }
}
