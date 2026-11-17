<?php

declare(strict_types=1);

namespace App\Tests\Service\DataStore;

use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Entity\PieceJointe;
use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;
use App\Service\DataStore\DataStoreFactory;
use App\Service\DataStore\RuntimeDataStore;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class DataStoreFilenameTest extends TestCase
{
    private const string CLIENT_FORMAT = '%NUMEROID_ETABLISSEMENT%_%DATE_PIECEJOINTE%_%NOM_PIECEJOINTE%_%ID_PIECEJOINTE%%EXTENSION_PIECEJOINTE%';

    private const string DEFAULT_FORMAT = '%NOM_PIECEJOINTE%%EXTENSION_PIECEJOINTE%';

    #[DataProvider('provideCommissionDocuments')]
    public function testConfiguredFilename(string $adapter, string $name): void
    {
        $repository = $this->createStub(EtablissementRepository::class);
        $repository->method('find')->willReturn(null);
        /** @var DossierRepository&MockObject $dossierRepository */
        $dossierRepository = $this->createStub(DossierRepository::class);
        $factory = new DataStoreFactory($repository, $dossierRepository, $adapter, '/data', '/files', self::CLIENT_FORMAT);
        $store = new RuntimeDataStore($factory);
        $pieceJointe = $this->createPieceJointe($name, new \DateTimeImmutable('2026-10-05 15:42:00'));

        $this->assertSame('42_2026-10-05_'.$name.'_123.odt', $store->getFormattedFilename($pieceJointe, 'dateCommission', 42));

        $defaultStore = new DataStoreFactory($repository, $dossierRepository, $adapter, '/data', '/files', self::DEFAULT_FORMAT)->create();
        $this->assertSame($defaultStore->getFilePath($pieceJointe, 'dateCommission', 42), $store->getFilePath($pieceJointe, 'dateCommission', 42));
        $this->assertSame($defaultStore->getURLPath($pieceJointe, 'dateCommission', 42), $store->getURLPath($pieceJointe, 'dateCommission', 42));
        $this->assertSame($name, $pieceJointe->getNom());
    }

    #[DataProvider('provideCommissionDocuments')]
    public function testInjectedDefaultFormatPreservesFilename(string $adapter, string $name): void
    {
        $repository = $this->createStub(EtablissementRepository::class);
        $repository->method('find')->willReturn(null);
        /** @var DossierRepository&MockObject $dossierRepository */
        $dossierRepository = $this->createStub(DossierRepository::class);
        $pieceJointe = $this->createPieceJointe($name, new \DateTime('2026-10-05 15:42:00'));
        $factory = new DataStoreFactory($repository, $dossierRepository, $adapter, '/data', '/files', self::DEFAULT_FORMAT);

        $this->assertSame($name.'.odt', $factory->create()->getFormattedFilename($pieceJointe, 'dateCommission', 42));
    }

    #[DataProvider('provideEmptyFormats')]
    public function testEmptyFormatIsRejected(string $adapter, string $format): void
    {
        $repository = $this->createStub(EtablissementRepository::class);
        /** @var DossierRepository&MockObject $dossierRepository */
        $dossierRepository = $this->createStub(DossierRepository::class);
        $factory = new DataStoreFactory($repository, $dossierRepository, $adapter, '/data', '/files', $format);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('PREVARISC_DATASTORE_FILE_FORMAT must not be empty.');

        $factory->create();
    }

    /**
     * @return \Iterator<string, array{string, string}>
     */
    public static function provideEmptyFormats(): \Iterator
    {
        yield 'simple empty' => ['Plugin_SimpleFileDataStore', ''];
        yield 'hashed empty' => ['Plugin_HashedFileDataStore', ''];
        yield 'simple whitespace' => ['Plugin_SimpleFileDataStore', '   '];
        yield 'hashed whitespace' => ['Plugin_HashedFileDataStore', '   '];
    }

    #[DataProvider('provideCommissionDocuments')]
    public function testMissingDateProducesEmptyDateToken(string $adapter, string $name): void
    {
        $repository = $this->createStub(EtablissementRepository::class);
        $repository->method('find')->willReturn(null);
        /** @var DossierRepository&MockObject $dossierRepository */
        $dossierRepository = $this->createStub(DossierRepository::class);
        $store = new DataStoreFactory($repository, $dossierRepository, $adapter, '/data', '/files', self::CLIENT_FORMAT)->create();

        $this->assertSame('42__'.$name.'_123.odt', $store->getFormattedFilename($this->createPieceJointe($name, null), 'dateCommission', 42));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function provideCommissionDocuments(): array
    {
        $cases = [];
        foreach (['Plugin_SimpleFileDataStore', 'Plugin_HashedFileDataStore'] as $adapter) {
            foreach (['Convocation', 'Ordre du jour', 'P.V.', 'Compte rendu'] as $name) {
                $cases[$adapter.' '.$name] = [$adapter, $name];
            }
        }

        return $cases;
    }

    /**
     * @param array<array{id: int, numero: ?string}> $etablissementData
     */
    #[DataProvider('provideLinkedObjectFilenameCases')]
    public function testLinkedObjectNumberToken(
        string $adapter,
        string $linkedObjectType,
        int $linkedObjectId,
        array $etablissementData,
        string $description,
        string $expected
    ): void {
        /** @var EtablissementRepository&MockObject $etablissementRepository */
        $etablissementRepository = $this->createMock(EtablissementRepository::class);
        /** @var DossierRepository&MockObject $dossierRepository */
        $dossierRepository = $this->createMock(DossierRepository::class);

        $etablissements = [];
        foreach ($etablissementData as $data) {
            /** @var Etablissement&MockObject $etablissement */
            $etablissement = $this->createMock(Etablissement::class);
            $etablissement->method('getId')->willReturn($data['id']);
            $etablissement->method('getNumero')->willReturn($data['numero']);
            $etablissements[] = $etablissement;
        }

        if ('dossier' === $linkedObjectType) {
            /** @var Dossier&MockObject $dossier */
            $dossier = $this->createMock(Dossier::class);
            $dossier->method('getEtablissements')->willReturn(new ArrayCollection($etablissements));
            $dossierRepository->method('find')->willReturn($dossier);
        } else {
            $etablissementRepository->method('find')->willReturn($etablissements[0] ?? null);
        }

        $store = new DataStoreFactory(
            $etablissementRepository,
            $dossierRepository,
            $adapter,
            '/data',
            '/files',
            '%NUMEROID_ETABLISSEMENT%'
        )->create();

        $this->assertSame($expected, $store->getFormattedFilename($this->createPieceJointe('Document', null, $description), $linkedObjectType, $linkedObjectId));
    }

    /**
     * @return array<string, array{string, string, int, array<array{id: int, numero: ?string}>, string, string}>
     */
    public static function provideLinkedObjectFilenameCases(): array
    {
        $cases = [];
        foreach (['Plugin_SimpleFileDataStore', 'Plugin_HashedFileDataStore'] as $adapter) {
            $cases[$adapter.' dossier description match'] = [
                $adapter, 'dossier', 42, [['id' => 11, 'numero' => 'ERP-11']], 'Rapport ERP-11', 'ERP-11',
            ];
            $cases[$adapter.' dossier joins establishment numbers'] = [
                $adapter, 'dossier', 42, [['id' => 11, 'numero' => 'NUM-11'], ['id' => 12, 'numero' => 'NUM-12']], 'Rapport', 'NUM-11_NUM-12',
            ];
            $cases[$adapter.' dossier without establishments'] = [
                $adapter, 'dossier', 42, [], 'Rapport', '42',
            ];
            $cases[$adapter.' dossier establishment without number'] = [
                $adapter, 'dossier', 42, [['id' => 11, 'numero' => null]], 'Rapport', '42',
            ];
            $cases[$adapter.' establishment with number'] = [
                $adapter, 'etablissement', 11, [['id' => 11, 'numero' => 'ERP-11']], '', 'ERP-11',
            ];
            $cases[$adapter.' establishment without number'] = [
                $adapter, 'etablissement', 11, [['id' => 11, 'numero' => null]], '', '11',
            ];
        }

        return $cases;
    }

    private function createPieceJointe(string $name, ?\DateTimeInterface $date, ?string $description = null): PieceJointe
    {
        $pieceJointe = new PieceJointe();
        $id = new \ReflectionProperty(PieceJointe::class, 'id');
        $id->setValue($pieceJointe, 123);

        $pieceJointe->setNom($name);
        $pieceJointe->setExtension('.odt');
        $pieceJointe->setDate($date);
        $pieceJointe->setDescription($description);

        return $pieceJointe;
    }
}
