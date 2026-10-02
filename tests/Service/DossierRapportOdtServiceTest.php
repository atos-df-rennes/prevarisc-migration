<?php

namespace App\Tests\Service;

use App\Constant\GenreEtablissement;
use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Entity\EtablissementCategorie;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use App\Repository\DateCommissionRepository;
use App\Repository\DossierAffectationRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementAdresseRepository;
use App\Repository\ListeDocumentConsulteRepository;
use App\Service\DataStore\DataStore;
use App\Service\Descriptif;
use App\Service\Dossier\DossierVisiteConvocationService;
use App\Service\DossierPrescriptionService;
use App\Service\DossierRapportOdtService;
use App\Service\Odt\OdtWriter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DossierRapportOdtServiceTest extends TestCase
{
    /** @var string */
    private $tmpDir;

    /** @var string */
    private $workingDir;

    /** @var string */
    private $memoryLimit;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/rapport_cellule_'.uniqid();
        mkdir($this->tmpDir);
        $this->workingDir = getcwd();
        chdir($this->tmpDir);
        // PclZip multiplie directement memory_limit ; une valeur numérique évite son incompatibilité avec "128M".
        $this->memoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '134217728');
    }

    protected function tearDown(): void
    {
        chdir($this->workingDir);
        ini_set('memory_limit', $this->memoryLimit);
        (new Filesystem())->remove($this->tmpDir);
    }

    /**
     * @dataProvider categorieProvider
     */
    public function testCategorieDansLeDocumentGenere(int $genreId, string $parentEtat, ?string $categorieParent, string $categorieAttendue): void
    {
        $etablissement = new Etablissement();
        $informations = new EtablissementInformations();
        $informations->setGenre($this->createConfiguredMock(Genre::class, ['getId' => $genreId]));
        $informations->setCategorie((new EtablissementCategorie())->setLibelle('2ème catégorie'));
        $etablissement->addInformations($informations);

        if ('absent' !== $parentEtat) {
            $parent = new Etablissement();
            $parentInformations = new EtablissementInformations();
            $parentInformations->setCategorie(null !== $categorieParent ? (new EtablissementCategorie())->setLibelle($categorieParent) : null);
            $parent->addInformations($parentInformations);
            if ('supprime' === $parentEtat) {
                $parent->setDateSuppression(new \DateTime('2026-01-01'));
            }
            $etablissement->addParent($parent);
        }

        $templatePath = $this->tmpDir.'/modele.odt';
        $generatedPath = $this->tmpDir.'/rapport.odt';
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($templatePath, \ZipArchive::CREATE));
        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');
        $zip->addFromString('content.xml', '<?xml version="1.0" encoding="UTF-8"?><office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"><office:body><office:text><text:p>Catégorie : {categorieEtab} / {etablissementLibelle} / {numPublic}</text:p></office:text></office:body></office:document-content>');
        $zip->addFromString('styles.xml', '<?xml version="1.0"?><office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>');
        $zip->addFromString('META-INF/manifest.xml', '<?xml version="1.0"?><manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0"/>');
        $zip->close();

        $dossier = new Dossier();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('find')->willReturnCallback(static function (string $class) use ($dossier, $etablissement) {
            return Etablissement::class === $class ? $etablissement : $dossier;
        });
        $entityManager->expects(self::exactly(2))->method('persist');
        $entityManager->expects(self::exactly(2))->method('flush');

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAssociative')->willReturnCallback(static function (string $sql) use ($genreId) {
            if (false !== strpos($sql, 'SELECT * FROM dossier WHERE')) {
                return ['HORSDELAI_DOSSIER' => '0', 'INCOMPLET_DOSSIER' => '0'];
            }
            if (false !== strpos($sql, 'SELECT * FROM etablissement WHERE')) {
                return array_merge(['ID_ETABLISSEMENT' => '101'], array_fill_keys([
                    'DESCTECH_DISTRIBUTION_CLOISONNEMENTTRAD_ETABLISSEMENT',
                    'DESCTECH_DISTRIBUTION_SECTEURS_ETABLISSEMENT',
                    'DESCTECH_DISTRIBUTION_COMPARTIMENTS_ETABLISSEMENT',
                    'DESCTECH_DESENFUMAGE_NATUREL_ETABLISSEMENT',
                    'DESCTECH_DESENFUMAGE_MECANIQUE_ETABLISSEMENT',
                    'DESCTECH_MOYENSSECOURS_COLONNESSECHES_ETABLISSEMENT',
                    'DESCTECH_MOYENSSECOURS_COLONNESHUMIDES_ETABLISSEMENT',
                    'DESCTECH_MOYENSSECOURS_RIA_ETABLISSEMENT',
                    'DESCTECH_MOYENSSECOURS_SPRINKLEUR_ETABLISSEMENT',
                    'DESCTECH_MOYENSSECOURS_BROUILLARDEAU_ETABLISSEMENT',
                    'DESCTECH_PCSECU_PRESENCE_ETABLISSEMENT',
                    'DESCTECH_SSI_PRESENCE_ETABLISSEMENT',
                    'DESCTECH_SERVICESECU_EL18_ETABLISSEMENT',
                    'DESCTECH_SERVICESECU_PERSONNELSDESIGNES_ETABLISSEMENT',
                ], '0'));
            }
            if (false !== strpos($sql, 'LEFT JOIN famille')) {
                return [
                    'ID_GENRE' => (string) $genreId,
                    'ID_CATEGORIE' => '2',
                    'LIBELLE_CATEGORIE' => '2ème catégorie',
                    'LIBELLE_ETABLISSEMENTINFORMATIONS' => 'Cellule Type M',
                    'EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS' => '42',
                ];
            }

            return false;
        });
        $connection->method('fetchAllAssociative')->willReturn([]);
        $dataStore = $this->createMock(DataStore::class);
        $dataStore->method('getFilePath')->willReturn($generatedPath);
        $prescriptionService = $this->createMock(DossierPrescriptionService::class);
        $prescriptionService->method('getPrescriptionsParType')->willReturn([]);
        $adresseRepository = $this->createMock(EtablissementAdresseRepository::class);
        $adresseRepository->method('get')->willReturn([]);
        $descriptif = $this->createMock(Descriptif::class);
        $descriptif->method('getForDossier')->willReturn(['capsule' => null, 'rubriques' => []]);
        $descriptif->method('getForEtablissement')->willReturn(['capsule' => null, 'rubriques' => []]);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/rapport.odt');
        $convocationService = $this->createMock(DossierVisiteConvocationService::class);
        $convocationService->method('getInfosConvocation')->willReturn(['dateVisite' => '', 'heureVisite' => '']);
        $dossierRepository = $this->createMock(DossierRepository::class);
        $dossierRepository->method('getTypeDossier')->willReturn(false);

        $service = new DossierRapportOdtService(
            $entityManager,
            $connection,
            $dataStore,
            $prescriptionService,
            $this->createMock(DossierAffectationRepository::class),
            $adresseRepository,
            $this->createMock(DateCommissionRepository::class),
            $descriptif,
            $this->createMock(ListeDocumentConsulteRepository::class),
            $dossierRepository,
            $urlGenerator,
            new OdtWriter(),
            $convocationService,
            $this->tmpDir,
            new NullLogger()
        );

        self::assertContains('/rapport.odt', $service->generer(1, 101, $templatePath));
        self::assertTrue($zip->open($generatedPath));
        $xml = $zip->getFromName('content.xml');
        $zip->close();
        $document = new \DOMDocument();
        self::assertTrue($document->loadXML($xml));
        self::assertSame('Catégorie : '.$categorieAttendue.' / Cellule Type M / 42', $document->getElementsByTagNameNS('*', 'p')->item(0)->textContent);
    }

    public function categorieProvider(): array
    {
        return [
            'cellule categorisee par parent' => [GenreEtablissement::CELLULE, 'actif', '5ème catégorie', '5ème'],
            'parent sans categorie' => [GenreEtablissement::CELLULE, 'actif', null, ''],
            'cellule sans parent' => [GenreEtablissement::CELLULE, 'absent', null, '2ème'],
            'parent supprime' => [GenreEtablissement::CELLULE, 'supprime', '5ème catégorie', '2ème'],
            'ERP inchange' => [GenreEtablissement::ERP, 'actif', '5ème catégorie', '2ème'],
        ];
    }
}
