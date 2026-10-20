<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Constant\TypeChamp;
use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class ListeTypeChampRubriqueStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            TypeChamp::TEXT => 'Champ texte',
            TypeChamp::TEXTAREA => 'Champ texte long',
            TypeChamp::SELECT => 'Liste',
            TypeChamp::NUMBER => 'Numérique',
            TypeChamp::CHECKBOX => 'Case à cocher',
            TypeChamp::PARENT => 'Parent',
            TypeChamp::DATE => 'Date',
        ] as $id => $type) {
            $this->connection->insert('listetypechamprubrique', ['ID_TYPECHAMP' => $id, 'TYPE' => $type]);
        }
    }
}
