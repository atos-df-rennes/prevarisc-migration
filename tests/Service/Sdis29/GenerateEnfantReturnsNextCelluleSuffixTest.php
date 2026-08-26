<?php

namespace App\Tests\Service\Sdis29;

use App\Service\Sdis29\NumeroIdGenerator;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class GenerateEnfantReturnsNextCelluleSuffixTest extends TestCase
{
    public function testGenerateEnfantReturnsNextCelluleSuffix(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn('01042.00');
        $connection->method('fetchAllAssociative')->willReturn([
            ['numeroid' => '01042.00'],
            ['numeroid' => '01042.01'],
        ]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $generator = new NumeroIdGenerator($entityManager);

        // Genre "3" = Cellule (cf. App\Constant\GenreEtablissement::CELLULE) : suffixe numérique.
        $result = $generator->generate('Brest', '3', 7);

        $this->assertSame('01042.00.02', $result);
    }
}
