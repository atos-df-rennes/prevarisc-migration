<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Constant\Fonction as FonctionId;
use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class FonctionStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            1 => 'Préfet',
            FonctionId::MAIRE => 'Maire',
            3 => "Maitre d'ouvrage",
            4 => "Maitre d'oeuvre",
            5 => 'Pétitionnaire demandeur',
            6 => 'Controller technique (organisme agréé)',
            FonctionId::EXPLOITANT => 'Exploitant',
            FonctionId::DIRECTEUR_UNIQUE_DE_SECURITE => 'Directeur unique de sécurité',
            FonctionId::RESPONSABLE_DE_SECURITE => 'Responsable de sécurité',
            10 => 'Participant',
            11 => 'Demandeur',
            12 => 'Simple utilisateur',
            FonctionId::PREVENTIONNISTE => 'Préventionniste',
            15 => 'Secrétariat',
            16 => 'Service informatique',
            FonctionId::PROPRIETAIRE => 'Propriétaire',
            99 => 'Utilisateur spécial',
        ] as $id => $libelle) {
            $this->connection->insert('fonction', ['ID_FONCTION' => $id, 'LIBELLE_FONCTION' => $libelle]);
        }
    }
}
