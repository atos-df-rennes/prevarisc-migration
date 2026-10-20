<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Constant\NomInterneCapsule;
use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class CapsuleRubriqueStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            [1, NomInterneCapsule::DESCRIPTIF_ETABLISSEMENT, "Descriptif de l'établissement"],
            [2, NomInterneCapsule::DESCRIPTIF_VERIFICATIONS_TECHNIQUES, 'Vérifications techniques du dossier'],
            [3, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_ETABLISSEMENT, "Effectifs & Dégagements de l'établissement"],
            [4, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER, 'Effectifs & Dégagements du dossier'],
        ] as [$id, $nomInterne, $nom]) {
            $this->connection->insert('capsulerubrique', [
                'ID_CAPSULERUBRIQUE' => $id,
                'NOM_INTERNE' => $nomInterne,
                'NOM' => $nom,
            ]);
        }
    }
}
