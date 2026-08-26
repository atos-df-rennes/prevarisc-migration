<?php

namespace App\Tests\Service\Sdis29;

use App\Service\Sdis29\NumeroIdGenerator;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class GenerateRacineAppendsCelluleSuffixTest extends TestCase
{
    public function testGenerateRacineAppendsCelluleSuffix(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(500);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $generator = new NumeroIdGenerator($entityManager);

        // Genre "3" = Cellule (cf. App\Constant\GenreEtablissement::CELLULE).
        $result = $generator->generate('Brest', '3', null);

        $this->assertSame('00500.00', $result);
    }
}
