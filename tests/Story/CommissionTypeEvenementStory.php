<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Constant\CommissionTypeEvenement;
use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class CommissionTypeEvenementStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            CommissionTypeEvenement::TYPE_COMMISSION_SALLE => 'Salle',
            CommissionTypeEvenement::TYPE_VISITE => 'Visite de sécurité',
            CommissionTypeEvenement::TYPE_GROUPE_VISITE => 'Groupe de visite',
        ] as $id => $libelle) {
            $this->connection->insert('commissiontypeevenement', [
                'ID_COMMISSIONTYPEEVENEMENT' => $id,
                'LIBELLE_COMMISSIONTYPEEVENEMENT' => $libelle,
            ]);
        }
    }
}
