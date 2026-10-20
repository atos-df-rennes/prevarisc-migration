<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Constant\CategorieEtablissement;
use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class CategorieStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            [1, '1ère catégorie', 'Plus de 1500 personnes'],
            [2, '2ème catégorie', 'De 701 à 1500 personnes'],
            [3, '3ème catégorie', 'De 301 à 700 personnes'],
            [4, '4ème catégorie', '300 personnes et moins'],
            [CategorieEtablissement::CINQUIEME, '5ème catégorie', 'Nombre très réduit de personnes'],
        ] as [$id, $libelle, $commentaire]) {
            $this->connection->insert('categorie', [
                'ID_CATEGORIE' => $id,
                'LIBELLE_CATEGORIE' => $libelle,
                'COMMENTAIRE_CATEGORIE' => $commentaire,
            ]);
        }
    }
}
