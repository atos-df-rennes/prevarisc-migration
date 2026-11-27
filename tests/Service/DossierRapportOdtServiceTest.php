<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Commune;
use App\Entity\Dossier;
use App\Entity\DossierDocumentUrbanisme;
use App\Entity\DossierPieceJointe;
use App\Entity\Groupement;
use App\Entity\PieceJointe;
use App\Entity\UtilisateurInformations;
use App\Repository\CommuneRepository;
use App\Repository\DateCommissionRepository;
use App\Repository\DossierAffectationRepository;
use App\Repository\EtablissementAdresseRepository;
use App\Repository\GroupementRepository;
use App\Repository\ListeDocumentConsulteRepository;
use App\Service\DataStore\DataStore;
use App\Service\Descriptif;
use App\Service\Dossier\DossierVisiteConvocationService;
use App\Service\DossierPrescriptionService;
use App\Service\DossierRapportOdtService;
use App\Service\Odt\OdtWriter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Odtphp\Odf;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DossierRapportOdtServiceTest extends TestCase
{
    #[DataProvider('sauvegardeRapportProvider')]
    public function testSauvegardeRapportDansUneTransaction(string $resultat): void
    {
        $repertoire = sys_get_temp_dir().'/rapport-odt-'.bin2hex(random_bytes(8));
        mkdir($repertoire);
        $chemin = $repertoire.'/rapport.odt';
        $transactionActive = false;
        $piecesPersistees = [];

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())->method('wrapInTransaction')->willReturnCallback(
            static function (callable $callback) use ($em, &$transactionActive): mixed {
                $transactionActive = true;
                try {
                    return $callback($em);
                } finally {
                    $transactionActive = false;
                }
            }
        );
        $em->expects($this->exactly(2))->method('persist')->willReturnCallback(
            function (object $piece) use (&$transactionActive, &$piecesPersistees): void {
                $this->assertTrue($transactionActive);
                $piecesPersistees[] = $piece;
            }
        );
        $em->expects($this->exactly(2))->method('flush')->willReturnCallback(
            function () use (&$transactionActive): void {
                $this->assertTrue($transactionActive);
            }
        );
        $dataStore = $this->createStub(DataStore::class);
        $dataStore->method('getFilePath')->willReturn($chemin);
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/rapport');
        $service = new DossierRapportOdtService(
            $em,
            $this->createStub(Connection::class),
            $dataStore,
            $this->createStub(DossierPrescriptionService::class),
            $this->createStub(DossierAffectationRepository::class),
            $this->createStub(EtablissementAdresseRepository::class),
            $this->createStub(DateCommissionRepository::class),
            $this->createStub(Descriptif::class),
            $this->createStub(ListeDocumentConsulteRepository::class),
            $urlGenerator,
            $this->createStub(OdtWriter::class),
            $this->createStub(DossierVisiteConvocationService::class),
            '',
            $this->createStub(LoggerInterface::class),
            $this->createStub(CommuneRepository::class),
            $this->createStub(GroupementRepository::class)
        );
        $sauvegardes = 0;
        $odf = new class(function (string $fichier) use ($resultat, $chemin, &$transactionActive, &$sauvegardes): void {
            ++$sauvegardes;
            $this->assertTrue($transactionActive);
            $this->assertSame($chemin, $fichier);
            if ('exception' === $resultat) {
                throw new \RuntimeException('Échec de sauvegarde');
            }
            if ('succes' === $resultat) {
                file_put_contents($chemin, 'rapport');
            }
        }) extends Odf {
            public function __construct(private readonly \Closure $sauvegarder)
            {
            }

            public function saveToDisk($file = null): void
            {
                ($this->sauvegarder)($file);
            }

            public function __destruct()
            {
            }
        };

        try {
            $erreur = null;
            $lien = null;
            try {
                $lien = new \ReflectionMethod($service, 'persistPieceJointe')->invoke(
                    $service,
                    $odf,
                    new Dossier(),
                    'modele.odt',
                    [],
                    ['LIBELLE_ETABLISSEMENTINFORMATIONS' => 'Établissement'],
                    [],
                    42
                );
            } catch (\RuntimeException $exception) {
                $erreur = $exception;
            }

            $this->assertInstanceOf(PieceJointe::class, $piecesPersistees[0]);
            $this->assertInstanceOf(DossierPieceJointe::class, $piecesPersistees[1]);
            $this->assertSame(1, $sauvegardes);
            if ('succes' === $resultat) {
                $this->assertNull($erreur);
                $this->assertSame("<a href='/rapport'>Ouvrir le rapport de l'établissement : Établissement</a>", $lien);
                $this->assertFileExists($chemin);
            } else {
                $this->assertInstanceOf(\RuntimeException::class, $erreur);
                $this->assertStringContainsString('exception' === $resultat ? 'Échec de sauvegarde' : 'Impossible de sauvegarder le rapport ODT', $erreur->getMessage());
                $this->assertFileDoesNotExist($chemin);
            }
        } finally {
            if (is_file($chemin)) {
                unlink($chemin);
            }
            rmdir($repertoire);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function sauvegardeRapportProvider(): iterable
    {
        yield 'écriture réussie' => ['succes'];
        yield 'écriture avec exception' => ['exception'];
        yield 'écriture sans fichier' => ['sans fichier'];
    }

    /**
     * @param list<string> $numeros
     */
    #[DataProvider('numerosUrbanismeProvider')]
    public function testNumerosUrbanismeDansLeRapport(array $numeros, string $attendu): void
    {
        $dossier = new Dossier();
        foreach ($numeros as $numero) {
            $dossier->addDocumentsUrbanisme(new DossierDocumentUrbanisme()->setNumero($numero));
        }

        $valeurFusion = null;
        $writer = $this->createMock(OdtWriter::class);
        $writer->expects($this->atLeastOnce())->method('setVariable')->willReturnCallback(
            static function (Odf $odf, string $nom, ?string $valeur) use (&$valeurFusion): void {
                if ('numDocUrba' === $nom) {
                    $valeurFusion = $valeur;
                }
            }
        );
        $service = new DossierRapportOdtService(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(Connection::class),
            $this->createStub(DataStore::class),
            $this->createStub(DossierPrescriptionService::class),
            $this->createStub(DossierAffectationRepository::class),
            $this->createStub(EtablissementAdresseRepository::class),
            $this->createStub(DateCommissionRepository::class),
            $this->createStub(Descriptif::class),
            $this->createStub(ListeDocumentConsulteRepository::class),
            $this->createStub(UrlGeneratorInterface::class),
            $writer,
            $this->createStub(DossierVisiteConvocationService::class),
            '',
            $this->createStub(LoggerInterface::class),
            $this->createStub(CommuneRepository::class),
            $this->createStub(GroupementRepository::class)
        );

        new \ReflectionMethod($service, 'fillChampsEtablissement')->invoke(
            $service,
            new class extends Odf {
                public function __construct()
                {
                }

                public function __destruct()
                {
                }
            },
            $dossier,
            [],
            [],
            [],
            ['INCOMPLET_DOSSIER' => '0'],
            1,
            1,
            ''
        );

        $this->assertSame($attendu, $valeurFusion);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function numerosUrbanismeProvider(): iterable
    {
        yield 'aucun numero' => [[], ''];
        yield 'un numero' => [['PC 123'], 'PC 123'];
        yield 'plusieurs numeros' => [['PC 123', 'AT 456', 'DP 789'], 'PC 123, AT 456, DP 789'];
    }

    #[DataProvider('serviceInstructeurProvider')]
    public function testCoordonneesDuServiceInstructeur(
        ?string $libelle,
        ?string $type,
        bool $serviceExiste,
        bool $contactExiste,
        bool $coordonneesAttendues
    ): void {
        $contact = new UtilisateurInformations()
            ->setPrenom('Camille')
            ->setNom('Contact instructeur')
            ->setMail('instruction@example.test');
        $commune = new Commune()->setUtilisateurInformations($contactExiste ? $contact : null);
        $groupement = new Groupement()->setInformations($contactExiste ? $contact : null);
        $groupement->addContact(new UtilisateurInformations()->setNom('Contact lie non utilise'));

        $communeRepository = $this->createMock(CommuneRepository::class);
        $groupementRepository = $this->createMock(GroupementRepository::class);
        $rechercheCommune = null !== $libelle && 'servInstCommune' === $type;
        $rechercheGroupement = null !== $libelle && 'servInstGrp' === $type;
        if ($rechercheCommune) {
            $communeRepository->expects($this->once())
                ->method('findFirstByLibelle')
                ->with($libelle)
                ->willReturn($serviceExiste ? $commune : null);
        } else {
            $communeRepository->expects($this->never())->method('findFirstByLibelle');
        }

        if ($rechercheGroupement) {
            $groupementRepository->expects($this->once())
                ->method('findOneBy')
                ->with(['libelle' => $libelle])
                ->willReturn($serviceExiste ? $groupement : null);
        } else {
            $groupementRepository->expects($this->never())->method('findOneBy');
        }

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('fetchAssociative');
        $service = new DossierRapportOdtService(
            $this->createStub(EntityManagerInterface::class),
            $connection,
            $this->createStub(DataStore::class),
            $this->createStub(DossierPrescriptionService::class),
            $this->createStub(DossierAffectationRepository::class),
            $this->createStub(EtablissementAdresseRepository::class),
            $this->createStub(DateCommissionRepository::class),
            $this->createStub(Descriptif::class),
            $this->createStub(ListeDocumentConsulteRepository::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(OdtWriter::class),
            $this->createStub(DossierVisiteConvocationService::class),
            '',
            $this->createStub(LoggerInterface::class),
            $communeRepository,
            $groupementRepository
        );
        $dossier = new Dossier()
            ->setServiceInstructeur($libelle)
            ->setTypeServiceInstructeur($type);

        $resultat = new \ReflectionMethod($service, 'getContactServiceInstructeur')->invoke($service, $dossier);

        $this->assertSame($coordonneesAttendues ? $contact : null, $resultat);
    }

    /**
     * @return iterable<string, array{?string, ?string, bool, bool, bool}>
     */
    public static function serviceInstructeurProvider(): iterable
    {
        yield 'commune instructrice, sans recherche du groupement homonyme' => ['Commune instructrice', 'servInstCommune', true, true, true];
        yield 'groupement, sans recherche de commune' => ['Commune instructrice', 'servInstGrp', true, true, true];
        yield 'commune introuvable' => ['Commune instructrice', 'servInstCommune', false, true, false];
        yield 'groupement introuvable, sans repli communal' => ['Commune instructrice', 'servInstGrp', false, true, false];
        yield 'commune sans fiche mairie' => ['Commune instructrice', 'servInstCommune', true, false, false];
        yield 'groupement sans fiche, meme avec un contact lie' => ['Commune instructrice', 'servInstGrp', true, false, false];
        yield 'saisie libre ou dossier sans type' => ['Service libre', null, true, true, false];
        yield 'service absent' => [null, 'servInstCommune', true, true, false];
    }
}
