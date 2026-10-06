<?php

declare(strict_types=1);

namespace App\Tests\Service\DataStore;

use App\Entity\PieceJointe;
use App\Repository\EtablissementRepository;
use App\Service\DataStore\DataStoreFactory;
use App\Service\DataStore\RuntimeDataStore;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $factory = new DataStoreFactory($repository, $adapter, '/data', '/files', self::CLIENT_FORMAT);
        $store = new RuntimeDataStore($factory);
        $pieceJointe = $this->createPieceJointe($name, new \DateTimeImmutable('2026-10-05 15:42:00'));

        $this->assertSame('42_2026-10-05_'.$name.'_123.odt', $store->getFormattedFilename($pieceJointe, 'dateCommission', 42));

        $defaultStore = new DataStoreFactory($repository, $adapter, '/data', '/files', self::DEFAULT_FORMAT)->create();
        $this->assertSame($defaultStore->getFilePath($pieceJointe, 'dateCommission', 42), $store->getFilePath($pieceJointe, 'dateCommission', 42));
        $this->assertSame($defaultStore->getURLPath($pieceJointe, 'dateCommission', 42), $store->getURLPath($pieceJointe, 'dateCommission', 42));
        $this->assertSame($name, $pieceJointe->getNom());
    }

    #[DataProvider('provideCommissionDocuments')]
    public function testInjectedDefaultFormatPreservesFilename(string $adapter, string $name): void
    {
        $repository = $this->createStub(EtablissementRepository::class);
        $repository->method('find')->willReturn(null);
        $pieceJointe = $this->createPieceJointe($name, new \DateTime('2026-10-05 15:42:00'));
        $factory = new DataStoreFactory($repository, $adapter, '/data', '/files', self::DEFAULT_FORMAT);

        $this->assertSame($name.'.odt', $factory->create()->getFormattedFilename($pieceJointe, 'dateCommission', 42));
    }

    #[DataProvider('provideEmptyFormats')]
    public function testEmptyFormatIsRejected(string $adapter, string $format): void
    {
        $repository = $this->createStub(EtablissementRepository::class);
        $factory = new DataStoreFactory($repository, $adapter, '/data', '/files', $format);

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
        $store = new DataStoreFactory($repository, $adapter, '/data', '/files', self::CLIENT_FORMAT)->create();

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

    private function createPieceJointe(string $name, ?\DateTimeInterface $date): PieceJointe
    {
        $pieceJointe = new PieceJointe();
        $id = new \ReflectionProperty(PieceJointe::class, 'id');
        $id->setValue($pieceJointe, 123);

        $pieceJointe->setNom($name);
        $pieceJointe->setExtension('.odt');
        $pieceJointe->setDate($date);

        return $pieceJointe;
    }
}
