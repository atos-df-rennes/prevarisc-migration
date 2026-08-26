<?php

namespace App\Tests\Service\Sdis29;

use App\Service\Sdis29\NumeroIdGenerator;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class GenerateEnfantReturnsNextLetterSuffixTest extends TestCase
{
    public function testGenerateEnfantReturnsNextLetterSuffix(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn('01042');
        $connection->method('fetchAllAssociative')->willReturn([
            ['numeroid' => '01042.A'],
            ['numeroid' => '01042.B'],
        ]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $generator = new NumeroIdGenerator($entityManager);

        // Genre "1" (non-Cellule) : suffixe alphabétique sur la racine à 5 caractères.
        $result = $generator->generate('Brest', '1', 7);

        $this->assertSame('01042.C', $result);
    }
}
