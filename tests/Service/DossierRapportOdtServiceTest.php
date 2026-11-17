<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Commune;
use App\Entity\Dossier;
use App\Entity\DossierDocumentUrbanisme;
use App\Entity\Groupement;
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
use Doctrine\ORM\EntityManagerInterface;
use Odtphp\Odf;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DossierRapportOdtServiceTest extends TestCase
{
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
