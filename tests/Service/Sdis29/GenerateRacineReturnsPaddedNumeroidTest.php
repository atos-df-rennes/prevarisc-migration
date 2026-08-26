<?php

namespace App\Tests\Service\Sdis29;

use App\Service\Sdis29\NumeroIdGenerator;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class GenerateRacineReturnsPaddedNumeroidTest extends TestCase
{
    public function testGenerateRacineReturnsPaddedNumeroid(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(1042);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $generator = new NumeroIdGenerator($entityManager);

        $result = $generator->generate('Brest', '1', null);

        $this->assertSame('01042', $result);
    }
}
