<?php

namespace App\Tests\Service\DataStore;

use App\Entity\PieceJointe;
use App\Repository\EtablissementRepository;
use App\Service\DataStore\DataStoreFactory;
use App\Service\DataStore\RuntimeDataStore;
use PHPUnit\Framework\TestCase;

final class DataStoreFilenameTest extends TestCase
{
    private const CLIENT_FORMAT = '%NUMEROID_ETABLISSEMENT%_%DATE_PIECEJOINTE%_%NOM_PIECEJOINTE%_%ID_PIECEJOINTE%%EXTENSION_PIECEJOINTE%';
    private const DEFAULT_FORMAT = '%NOM_PIECEJOINTE%%EXTENSION_PIECEJOINTE%';

    /**
     * @dataProvider provideCommissionDocuments
     */
    public function testConfiguredFilename(string $adapter, string $name): void
    {
        /** @var EtablissementRepository&\PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = $this->createMock(EtablissementRepository::class);
        $repository->method('find')->willReturn(null);
        $factory = new DataStoreFactory($repository, $adapter, '/data', '/files', self::CLIENT_FORMAT);
        $store = new RuntimeDataStore($factory);
        $pieceJointe = $this->createPieceJointe($name, new \DateTimeImmutable('2026-10-05 15:42:00'));

        self::assertSame(
            '42_2026-10-05_'.$name.'_123.odt',
            $store->getFormattedFilename($pieceJointe, 'dateCommission', 42)
        );

        $defaultStore = (new DataStoreFactory($repository, $adapter, '/data', '/files', self::DEFAULT_FORMAT))->create();
        self::assertSame(
            $defaultStore->getFilePath($pieceJointe, 'dateCommission', 42),
            $store->getFilePath($pieceJointe, 'dateCommission', 42)
        );
        self::assertSame(
            $defaultStore->getURLPath($pieceJointe, 'dateCommission', 42),
            $store->getURLPath($pieceJointe, 'dateCommission', 42)
        );
        self::assertSame($name, $pieceJointe->getNom());
    }

    /**
     * @dataProvider provideCommissionDocuments
     */
    public function testInjectedDefaultFormatPreservesFilename(string $adapter, string $name): void
    {
        /** @var EtablissementRepository&\PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = $this->createMock(EtablissementRepository::class);
        $repository->method('find')->willReturn(null);
        $pieceJointe = $this->createPieceJointe($name, new \DateTime('2026-10-05 15:42:00'));
        $factory = new DataStoreFactory($repository, $adapter, '/data', '/files', self::DEFAULT_FORMAT);

        self::assertSame(
            $name.'.odt',
            $factory->create()->getFormattedFilename($pieceJointe, 'dateCommission', 42)
        );
    }

    /**
     * @dataProvider provideEmptyFormats
     */
    public function testEmptyFormatIsRejected(string $adapter, string $format): void
    {
        /** @var EtablissementRepository&\PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = $this->createMock(EtablissementRepository::class);
        $factory = new DataStoreFactory($repository, $adapter, '/data', '/files', $format);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PREVARISC_DATASTORE_FILE_FORMAT must not be empty.');

        $factory->create();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function provideEmptyFormats(): array
    {
        return [
            'simple empty' => ['Plugin_SimpleFileDataStore', ''],
            'hashed empty' => ['Plugin_HashedFileDataStore', ''],
            'simple whitespace' => ['Plugin_SimpleFileDataStore', '   '],
            'hashed whitespace' => ['Plugin_HashedFileDataStore', '   '],
        ];
    }

    /**
     * @dataProvider provideCommissionDocuments
     */
    public function testMissingDateProducesEmptyDateToken(string $adapter, string $name): void
    {
        /** @var EtablissementRepository&\PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = $this->createMock(EtablissementRepository::class);
        $repository->method('find')->willReturn(null);
        $store = (new DataStoreFactory($repository, $adapter, '/data', '/files', self::CLIENT_FORMAT))->create();

        self::assertSame(
            '42__'.$name.'_123.odt',
            $store->getFormattedFilename($this->createPieceJointe($name, null), 'dateCommission', 42)
        );
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
        $id->setAccessible(true);
        $id->setValue($pieceJointe, 123);
        $pieceJointe->setNom($name);
        $pieceJointe->setExtension('.odt');
        $pieceJointe->setDate($date);

        return $pieceJointe;
    }
}
