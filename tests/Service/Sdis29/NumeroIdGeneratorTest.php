<?php

declare(strict_types=1);

namespace App\Tests\Service\Sdis29;

use App\Entity\Etablissement;
use App\Repository\EtablissementRepository;
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
        $generator = new NumeroIdGenerator($entityManager, $this->createStub(EtablissementRepository::class));

        $this->expectException(\InvalidArgumentException::class);

        $generator->generate('Arrondissement inconnu', '1', null);
    }

    public function testGenerateRacineReturnsPaddedNumeroid(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('fetchOne')
            ->with(self::anything(), ['minId' => 1000, 'maxId' => 39999])
            ->willReturn(1042);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $generator = new NumeroIdGenerator($entityManager, $this->createStub(EtablissementRepository::class));

        $result = $generator->generate('Brest', '1', null);

        $this->assertSame('01042', $result);
    }

    public function testGenerateRacineAppendsCelluleSuffix(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('fetchOne')->willReturn(500);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $generator = new NumeroIdGenerator($entityManager, $this->createStub(EtablissementRepository::class));

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
        $generator = $this->createEnfantGenerator('01042', '01042', ['01042.01']);

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
        $generator = $this->createEnfantGenerator('01042.00', '01042.00', ['01042.00', '01042.00.01']);

        $result = $generator->generate('Brest', '3', 7);

        $this->assertSame('01042.00.02', $result);
    }

    public function testGenerateEnfantReturnsNextLetterSuffixForNonCelluleGenre(): void
    {
        $generator = $this->createEnfantGenerator('01042', '01042', ['01042.A', '01042.B']);

        // Genre "1" (non-Cellule) : suffixe alphabétique sur la racine à 5 caractères.
        $result = $generator->generate('Brest', '1', 7);

        $this->assertSame('01042.C', $result);
    }

    /**
     * @param list<string> $numerosExistants
     */
    private function createEnfantGenerator(string $numeroPere, string $prefixeAttendu, array $numerosExistants): NumeroIdGenerator
    {
        $repository = $this->createMock(EtablissementRepository::class);
        $repository->expects($this->once())->method('find')
            ->with(7)
            ->willReturn(new Etablissement()->setNumero($numeroPere));
        $repository->expects($this->once())->method('findNumerosByPrefix')
            ->with($prefixeAttendu)
            ->willReturn($numerosExistants);

        return new NumeroIdGenerator($this->createStub(EntityManagerInterface::class), $repository);
    }
}
