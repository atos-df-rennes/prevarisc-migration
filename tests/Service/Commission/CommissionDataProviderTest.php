<?php

namespace App\Tests\Service\Commission;

use App\Constant\GenreEtablissement;
use App\Repository\EtablissementAdresseRepository;
use App\Service\Commission\CommissionDataProvider;
use App\Service\Commission\CommissionMembreEligibilityChecker;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\DebugStack;
use PHPUnit\Framework\TestCase;

class CommissionDataProviderTest extends TestCase
{
    /** @var Connection */
    private $connection;

    /** @var CommissionDataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        foreach ([
            'CREATE TABLE etablissement (ID_ETABLISSEMENT INTEGER PRIMARY KEY, DATESUPPRESSION_ETABLISSEMENT TEXT)',
            'CREATE TABLE etablissementinformations (ID_ETABLISSEMENTINFORMATIONS INTEGER PRIMARY KEY, ID_ETABLISSEMENT INTEGER, DATE_ETABLISSEMENTINFORMATIONS TEXT, LIBELLE_ETABLISSEMENTINFORMATIONS TEXT, ID_GENRE INTEGER, ID_CATEGORIE INTEGER, ID_TYPEACTIVITE INTEGER, ID_TYPE INTEGER)',
            'CREATE TABLE categorie (ID_CATEGORIE INTEGER PRIMARY KEY, LIBELLE_CATEGORIE TEXT)',
            'CREATE TABLE typeactivite (ID_TYPEACTIVITE INTEGER PRIMARY KEY, LIBELLE_ACTIVITE TEXT)',
            'CREATE TABLE genre (ID_GENRE INTEGER PRIMARY KEY, LIBELLE_GENRE TEXT)',
            'CREATE TABLE type (ID_TYPE INTEGER PRIMARY KEY, LIBELLE_TYPE TEXT)',
            'CREATE TABLE etablissementlie (ID_ETABLISSEMENT INTEGER, ID_FILS_ETABLISSEMENT INTEGER)',
            'CREATE TABLE dossier (ID_DOSSIER INTEGER PRIMARY KEY, TYPE_DOSSIER INTEGER, DATEVISITE_DOSSIER TEXT)',
            'CREATE TABLE etablissementdossier (ID_DOSSIER INTEGER, ID_ETABLISSEMENT INTEGER)',
        ] as $sql) {
            $this->connection->executeStatement($sql);
        }
        $this->connection->insert('categorie', ['ID_CATEGORIE' => 2, 'LIBELLE_CATEGORIE' => '2ème catégorie']);
        $this->connection->insert('categorie', ['ID_CATEGORIE' => 5, 'LIBELLE_CATEGORIE' => '5ème catégorie']);

        $adresseRepository = $this->createMock(EtablissementAdresseRepository::class);
        $adresseRepository->method('get')->willReturn([]);
        $this->provider = new CommissionDataProvider($this->connection, $adresseRepository);
    }

    protected function tearDown(): void
    {
        $this->connection->close();
    }

    /**
     * @dataProvider categorieProvider
     */
    public function testCategorieDuParentEnBatch(int $genreId, string $parentEtat, ?int $categorieParent, ?int $categorieAttendue): void
    {
        foreach ([101, 102] as $id) {
            $this->connection->insert('etablissement', ['ID_ETABLISSEMENT' => $id]);
            $this->insertInformations($id, $id, $genreId, 2, '2026-01-01');
        }
        if ('absent' !== $parentEtat) {
            $this->connection->insert('etablissement', [
                'ID_ETABLISSEMENT' => 200,
                'DATESUPPRESSION_ETABLISSEMENT' => 'supprime' === $parentEtat ? '2026-01-01' : null,
            ]);
            foreach ([101, 102] as $id) {
                $this->connection->insert('etablissementlie', ['ID_ETABLISSEMENT' => 200, 'ID_FILS_ETABLISSEMENT' => $id]);
            }
            if ('sans-fiche' !== $parentEtat) {
                $this->insertInformations(201, 200, GenreEtablissement::ERP, 2, '2025-01-01');
                $this->insertInformations(202, 200, GenreEtablissement::ERP, $categorieParent, '2026-01-01');
            }
        }

        $logger = new DebugStack();
        $this->connection->getConfiguration()->setSQLLogger($logger);
        $result = $this->provider->batchGetEtablissementInfos([101, 102, 101]);

        self::assertCount(2, $result);
        self::assertCount(6, $logger->queries, 'La categorie des parents est chargee en batch, sans requete par cellule.');
        foreach ([101, 102] as $id) {
            self::assertSame(null === $categorieAttendue ? null : (string) $categorieAttendue, $result[$id]['informations']['ID_CATEGORIE']);
            self::assertSame(null === $categorieAttendue ? null : (5 === $categorieAttendue ? '5ème catégorie' : '2ème catégorie'), $result[$id]['informations']['LIBELLE_CATEGORIE']);
            self::assertSame('Etablissement '.$id, $result[$id]['informations']['LIBELLE_ETABLISSEMENTINFORMATIONS']);
            self::assertSame('10', $result[$id]['informations']['ID_TYPEACTIVITE']);
            if ([] !== $result[$id]['parents']) {
                self::assertSame(['ID_FILS_ETABLISSEMENT', 'LIBELLE_ETABLISSEMENTINFORMATIONS'], array_keys($result[$id]['parents'][0]));
            }
        }

        $membre = [
            'categories' => [5 => ['ID_COMMISSIONMEMBRE' => 1]],
            'types' => [10 => ['ID_COMMISSIONMEMBRE' => 1]],
            'dossiertypes' => [1 => ['ID_COMMISSIONMEMBRE' => 1]],
        ];
        self::assertSame(5 === $categorieAttendue, (new CommissionMembreEligibilityChecker())->isDossierConcerneMembre($membre, ['TYPE_DOSSIER' => 1, 'infosEtab' => $result[101]]));
    }

    public function categorieProvider(): array
    {
        return [
            'parent derniere fiche' => [GenreEtablissement::CELLULE, 'actif', 5, 5],
            'parent sans categorie' => [GenreEtablissement::CELLULE, 'actif', null, null],
            'parent sans fiche' => [GenreEtablissement::CELLULE, 'sans-fiche', null, null],
            'parent supprime' => [GenreEtablissement::CELLULE, 'supprime', 5, 2],
            'sans parent' => [GenreEtablissement::CELLULE, 'absent', null, 2],
            'ERP inchange' => [GenreEtablissement::ERP, 'actif', 5, 2],
            'meme categorie' => [GenreEtablissement::CELLULE, 'actif', 2, 2],
        ];
    }

    public function testBatchVideSansRequete(): void
    {
        $logger = new DebugStack();
        $this->connection->getConfiguration()->setSQLLogger($logger);
        self::assertSame([], $this->provider->batchGetEtablissementInfos([]));
        self::assertSame([], $logger->queries);
    }

    private function insertInformations(int $id, int $etablissementId, int $genreId, ?int $categorie, string $date): void
    {
        $this->connection->insert('etablissementinformations', [
            'ID_ETABLISSEMENTINFORMATIONS' => $id,
            'ID_ETABLISSEMENT' => $etablissementId,
            'DATE_ETABLISSEMENTINFORMATIONS' => $date,
            'LIBELLE_ETABLISSEMENTINFORMATIONS' => 'Etablissement '.$etablissementId,
            'ID_GENRE' => $genreId,
            'ID_CATEGORIE' => $categorie,
            'ID_TYPEACTIVITE' => 10,
        ]);
    }
}
