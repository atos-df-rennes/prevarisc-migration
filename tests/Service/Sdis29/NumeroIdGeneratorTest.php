<?php

namespace App\Tests\Service\Sdis29;

use App\Service\Sdis29\NumeroIdGenerator;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Teste le schéma de numérotation SDIS29 (plages par arrondissement, puis
 * suffixes numériques pour les cellules et alphabétiques pour les autres
 * établissements enfants). Portage de
 * SDIS29_EtablissementController::generateidAction() (legacy Zend).
 */
final class NumeroIdGeneratorTest extends TestCase
{
    public function testGenerateThrowsForUnknownArrondissement(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $generator = new NumeroIdGenerator($entityManager);

        $this->expectException(\InvalidArgumentException::class);

        $generator->generate('Arrondissement inconnu', '1', null);
    }

    public function testGenerateRacineReturnsPaddedNumeroid(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(1042);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $generator = new NumeroIdGenerator($entityManager);

        $result = $generator->generate('Brest', '1', null);

        self::assertSame('01042', $result);
    }

    public function testGenerateRacineAppendsCelluleSuffix(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(500);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $generator = new NumeroIdGenerator($entityManager);

        // Genre "3" = Cellule (cf. App\Constant\GenreEtablissement::CELLULE).
        $result = $generator->generate('Brest', '3', null);

        self::assertSame('00500.00', $result);
    }

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

        self::assertSame('01042.C', $result);
    }

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

        self::assertSame('01042.00.02', $result);
    }
}
