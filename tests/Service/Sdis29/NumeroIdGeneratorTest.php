<?php

declare(strict_types=1);

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
 *
 * generate($arrondissement, $idGenre, $idPere) a deux modes, mutuellement
 * exclusifs selon $idPere :
 *  - $idPere === null : nouvelle "racine" (premier numéro libre dans la plage
 *    de l'arrondissement) ;
 *  - $idPere !== null : nouvel établissement "enfant" d'un établissement
 *    père existant (ex : une Cellule au sein d'un Site, ou tout autre
 *    établissement lié). Le numéro du père (NUMEROID_ETABLISSEMENT, tel
 *    quel, y compris son éventuel suffixe existant) sert de préfixe/base
 *    au nouveau numéro.
 */
final class NumeroIdGeneratorTest extends TestCase
{
    public function testGenerateThrowsForUnknownArrondissement(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $generator = new NumeroIdGenerator($entityManager);

        $this->expectException(\InvalidArgumentException::class);

        $generator->generate('Arrondissement inconnu', '1', null);
    }

    public function testGenerateRacineReturnsPaddedNumeroid(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('fetchOne')
            ->with(self::anything(), ['minId' => 1000, 'maxId' => 39999])
            ->willReturn(1042);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $generator = new NumeroIdGenerator($entityManager);

        $result = $generator->generate('Brest', '1', null);

        $this->assertSame('01042', $result);
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

        $this->assertSame('00500.00', $result);
    }

    /**
     * Cas le plus courant : le père est un établissement "simple" (numéro
     * non suffixé, ex : un ERP de numéro "01042"). La nouvelle Cellule
     * enfant reprend ce numéro tel quel et lui ajoute un suffixe à deux
     * chiffres.
     */
    public function testGenerateEnfantAppendsCelluleSuffixToPlainParentNumeroid(): void
    {
        $connection = $this->createMock(Connection::class);
        // Premier appel : lecture du numéro du père (idPere = 7 bien transmis en paramètre lié).
        $connection->expects($this->once())->method('fetchOne')
            ->with(self::anything(), ['idPere' => 7])
            ->willReturn('01042');
        // Second appel : recherche des enfants existants partageant ce même numéro de père.
        $connection->expects($this->once())->method('fetchAllAssociative')
            ->with(self::anything(), ['length' => 5, 'numeroSearch' => '01042'])
            ->willReturn([
                ['numeroid' => '01042.01'],
            ]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $generator = new NumeroIdGenerator($entityManager);

        $result = $generator->generate('Brest', '3', 7);

        $this->assertSame('01042.02', $result);
    }

    /**
     * Cas particulier : le père est lui-même un Site (numéro déjà suffixé
     * ".00", cf. testGenerateRacineAppendsCelluleSuffix). La nouvelle Cellule
     * enfant reprend le numéro complet du Site (avec son suffixe) comme
     * base, ce qui produit un numéro à trois segments (ex : "01042.00.02").
     * Comportement fidèle au legacy : $identifiant = $numeroid.".".$compteur,
     * où $numeroid est le numéro du père tel que stocké en base, sans
     * troncature.
     */
    public function testGenerateEnfantAppendsCelluleSuffixToSiteParentNumeroid(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('fetchOne')
            ->with(self::anything(), ['idPere' => 7])
            ->willReturn('01042.00');
        $connection->expects($this->once())->method('fetchAllAssociative')
            ->with(self::anything(), ['length' => 8, 'numeroSearch' => '01042.00'])
            ->willReturn([
                ['numeroid' => '01042.00'],
                ['numeroid' => '01042.00.01'],
            ]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $generator = new NumeroIdGenerator($entityManager);

        $result = $generator->generate('Brest', '3', 7);

        $this->assertSame('01042.00.02', $result);
    }

    public function testGenerateEnfantReturnsNextLetterSuffixForNonCelluleGenre(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('fetchOne')
            ->with(self::anything(), ['idPere' => 7])
            ->willReturn('01042');
        $connection->expects($this->once())->method('fetchAllAssociative')
            ->with(self::anything(), ['length' => 5, 'numeroSearch' => '01042'])
            ->willReturn([
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
