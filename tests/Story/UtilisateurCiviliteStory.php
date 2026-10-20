<?php

declare(strict_types=1);

namespace App\Tests\Story;

use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class UtilisateurCiviliteStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([1 => 'Monsieur', 2 => 'Madame'] as $id => $libelle) {
            $this->connection->insert('utilisateurcivilite', [
                'ID_UTILISATEURCIVILITE' => $id,
                'LIBELLE_UTILISATEURCIVILITE' => $libelle,
            ]);
        }
    }
}
