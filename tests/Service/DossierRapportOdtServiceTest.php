<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Avis;
use App\Entity\Commission;
use App\Entity\Dossier;
use App\Entity\DossierType;
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
use Odtphp\Odf;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DossierRapportOdtServiceTest extends TestCase
{
    private DossierRapportOdtService $service;

    private Connection&MockObject $connection;

    private Odf $odf;

    /** @var array<string, string|null> */
    private array $champs = [];

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        // Cible opaque pour OdtWriter, sans ouverture ni nettoyage de fichier ODT.
        $this->odf = new class extends Odf {
            public function __construct()
            {
            }

            public function __destruct()
            {
            }
        };
        $writer = $this->createMock(OdtWriter::class);
        $writer->expects($this->atLeastOnce())->method('setVariable')
            ->willReturnCallback(function (Odf $target, string $name, ?string $value): void {
                $this->assertSame($this->odf, $target);
                $this->champs[$name] = $value;
            });

        $this->service = new DossierRapportOdtService(
            $this->createStub(EntityManagerInterface::class),
            $this->connection,
            $this->createStub(DataStore::class),
            $this->createStub(DossierPrescriptionService::class),
            $this->createStub(DossierAffectationRepository::class),
            $this->createStub(EtablissementAdresseRepository::class),
            $this->createStub(DateCommissionRepository::class),
            $this->createStub(Descriptif::class),
            $this->createStub(ListeDocumentConsulteRepository::class),
            $this->createStub(DossierRepository::class),
            $this->createStub(UrlGeneratorInterface::class),
            $writer,
            $this->createStub(DossierVisiteConvocationService::class),
            '/unused',
            $this->createStub(LoggerInterface::class)
        );
    }

    public function testChampsSimplesEtTypeProviennentDuDossierCharge(): void
    {
        $dossier = new Dossier();
        $dossier->setObjet('Objet & <texte>');
        $dossier->setAnalyseDeRisque('Analyse');
        $dossier->setDescriptifAnalyseRisque('Autre analyse, hors champ');
        $dossier->setObservations("Observation\nsur deux lignes");
        $dossier->setDescriptif('Descriptif');
        $dossier->setDescriptifGeneral('Autre descriptif, hors champ');
        $dossier->setFacteurDangerosite(3);
        $dossier->setDemandeur('Demandeur');
        $dossier->setReglesDerogation('Regles');
        $dossier->setJustificationDerogation('Justification');
        $dossier->setMesuresCompensatoires('Compensation');
        $dossier->setMesuresComplementaires('Complement');
        $dossier->setType(new DossierType()->setLibelle('Etude'));

        $this->attendreDocumentsManquants();
        $this->remplirDossier($dossier, [
            'OBJET_DOSSIER' => 'Valeur brute non utilisee',
            'ANOMALIE_DOSSIER' => 'Valeur brute non utilisee',
            'DESCRIPTIF_DOSSIER' => 'Valeur brute non utilisee',
            'FACTDANGE_DOSSIER' => '9',
        ]);

        $attendus = [
            'objetDossier' => 'Objet & <texte>',
            'analyseRisque' => 'Analyse',
            'observationDossier' => "Observation\nsur deux lignes",
            'descriptifDossier' => 'Descriptif',
            'criticite' => '3',
            'demandeurDossier' => 'Demandeur',
            'reglesDerog' => 'Regles',
            'justificationDerog' => 'Justification',
            'mesuresCompensatoiresDerog' => 'Compensation',
            'mesuresComplementairesDerog' => 'Complement',
            'typeDossier' => 'Etude',
            'natureDossier' => 'Nature issue du SQL conserve',
        ];
        foreach ($attendus as $champ => $valeur) {
            $this->assertSame($valeur, $this->champs[$champ], $champ);
        }
    }

    #[DataProvider('valeursAbsentes')]
    public function testValeursAbsentesEtCriticiteZero(?string $texte, ?int $criticite, string $criticiteAttendue): void
    {
        $dossier = new Dossier();
        $dossier->setObjet($texte);
        $dossier->setAnalyseDeRisque($texte);
        $dossier->setObservations($texte);
        $dossier->setDescriptif($texte);
        $dossier->setDemandeur($texte);
        $dossier->setReglesDerogation($texte);
        $dossier->setJustificationDerogation($texte);
        $dossier->setMesuresCompensatoires($texte);
        $dossier->setMesuresComplementaires($texte);
        $dossier->setFacteurDangerosite($criticite);
        $this->attendreDocumentsManquants();

        $this->remplirDossier($dossier);

        foreach (['objetDossier', 'analyseRisque', 'observationDossier', 'descriptifDossier', 'demandeurDossier', 'reglesDerog', 'justificationDerog', 'mesuresCompensatoiresDerog', 'mesuresComplementairesDerog', 'typeDossier'] as $champ) {
            $this->assertSame('', $this->champs[$champ], $champ);
        }

        $this->assertSame($criticiteAttendue, $this->champs['criticite']);
    }

    /** @return iterable<string, array{?string, ?int, string}> */
    public static function valeursAbsentes(): iterable
    {
        yield 'null' => [null, null, ''];
        yield 'vide et zero' => ['', 0, '0'];
    }

    #[DataProvider('libellesCommission')]
    public function testRepliCommissionSansLectureSql(?int $id, ?string $libelle, string $attendu): void
    {
        $dossier = new Dossier();
        if (null !== $id) {
            $commission = new Commission();
            new \ReflectionProperty(Commission::class, 'id')->setValue($commission, $id);
            if (null !== $libelle) {
                $commission->setLibelle($libelle);
            }

            $dossier->setCommission($commission);
        }

        $this->attendreDocumentsManquants();

        $this->remplirDossier($dossier, ['COMMISSION_DOSSIER' => '99']);

        $this->assertSame($attendu, $this->champs['libelle_commission']);
    }

    /** @return iterable<string, array{?int, ?string, string}> */
    public static function libellesCommission(): iterable
    {
        yield 'absente' => [null, null, 'Aucune commission'];
        yield 'identifiant zero' => [0, 'Commission', 'Aucune commission'];
        yield 'libelle null' => [7, null, 'Aucune commission'];
        yield 'libelle vide' => [7, '', 'Aucune commission'];
        yield 'libelle zero' => [7, '0', 'Aucune commission'];
        yield 'renseignee' => [7, 'Commission', 'Commission'];
    }

    #[DataProvider('servicesInstructeursAbsents')]
    public function testServiceInstructeurAbsentNeRecherchePasDeContact(?string $valeur): void
    {
        $dossier = new Dossier();
        new \ReflectionProperty(Dossier::class, 'serviceInstructeur')->setValue($dossier, $valeur);
        $this->attendreDocumentsManquants();

        $this->remplirDossier($dossier, ['SERVICEINSTRUC_DOSSIER' => 'Valeur brute non utilisee'], '35000');

        foreach (['servInstructeur', 'servInstructeurPrenomContact', 'servInstructeurNomContact', 'servInstructeurMail'] as $champ) {
            $this->assertSame('', $this->champs[$champ], $champ);
        }
    }

    /** @return iterable<string, array{?string}> */
    public static function servicesInstructeursAbsents(): iterable
    {
        yield 'null' => [null];
        yield 'vide legacy' => [''];
        yield 'zero legacy' => ['0'];
    }

    #[DataProvider('resolutionContacts')]
    public function testResolutionContactInstructeurConserveLaPrioriteDuGroupement(bool $groupementTrouve, ?string $numInsee): void
    {
        $dossier = new Dossier();
        $dossier->setServiceInstructeur('Service instructeur');

        $contact = [
            'PRENOM_UTILISATEURINFORMATIONS' => 'Prenom',
            'NOM_UTILISATEURINFORMATIONS' => 'Nom',
            'MAIL_UTILISATEURINFORMATIONS' => 'contact@example.test',
        ];
        $nombreRequetes = !$groupementTrouve && null !== $numInsee ? 3 : 2;
        $this->connection->expects($this->exactly($nombreRequetes))->method('fetchAssociative')
            ->willReturnCallback(static function (string $sql, array $params) use ($groupementTrouve, $numInsee, $contact): array|false {
                if (str_contains($sql, 'FROM dossierdocmanquant')) {
                    self::assertSame(['id' => 42], $params);

                    return false;
                }

                if (str_contains($sql, 'FROM groupement g')) {
                    self::assertSame(['libelle' => 'Service instructeur'], $params);

                    return $groupementTrouve ? $contact : false;
                }

                self::assertStringContainsString('FROM adressecommune ac', $sql);
                self::assertSame(['numInsee' => $numInsee], $params);

                return $contact;
            });

        $this->remplirDossier($dossier, ['SERVICEINSTRUC_DOSSIER' => 'Valeur brute non utilisee'], $numInsee);

        $contactTrouve = $groupementTrouve || null !== $numInsee;
        $this->assertSame('Service instructeur', $this->champs['servInstructeur']);
        $this->assertSame($contactTrouve ? 'Prenom' : '', $this->champs['servInstructeurPrenomContact']);
        $this->assertSame($contactTrouve ? 'Nom' : '', $this->champs['servInstructeurNomContact']);
        $this->assertSame($contactTrouve ? 'contact@example.test' : '', $this->champs['servInstructeurMail']);
    }

    /** @return iterable<string, array{bool, ?string}> */
    public static function resolutionContacts(): iterable
    {
        yield 'groupement prioritaire' => [true, '35000'];
        yield 'repli commune' => [false, '35000'];
        yield 'sans commune' => [false, null];
    }

    #[DataProvider('libellesAvis')]
    public function testAvisDossierSansLectureSqlEtAvisCommissionOrphelinConserve(?int $id, ?string $libelle, string $attendu): void
    {
        $dossier = new Dossier();
        if (null !== $id) {
            $avis = new Avis();
            new \ReflectionProperty(Avis::class, 'id')->setValue($avis, $id);
            if (null !== $libelle) {
                $avis->setLibelle($libelle);
            }

            $dossier->setAvis($avis);
        }

        $this->connection->expects($this->exactly(2))->method('fetchAssociative')
            ->willReturnCallback(static function (string $sql, array $params): false {
                if (str_contains($sql, 'FROM dossierdocurba')) {
                    self::assertSame(['id' => 42], $params);
                } else {
                    self::assertSame('SELECT LIBELLE_AVIS FROM avis WHERE ID_AVIS = :id', $sql);
                    self::assertSame(['id' => '99'], $params);
                }

                return false;
            });

        new \ReflectionMethod(DossierRapportOdtService::class, 'fillChampsEtablissement')->invoke(
            $this->service,
            $this->odf,
            $dossier,
            [],
            [],
            [],
            ['AVIS_DOSSIER' => '98', 'AVIS_DOSSIER_COMMISSION' => '99', 'INCOMPLET_DOSSIER' => '1'],
            42,
            17,
            ''
        );

        $this->assertSame($attendu, $this->champs['avisDossier']);
        $this->assertSame('', $this->champs['avisDossierCommission']);
        $this->assertSame('Incomplet', $this->champs['etatDossier']);
    }

    /** @return iterable<string, array{?int, ?string, string}> */
    public static function libellesAvis(): iterable
    {
        yield 'absent' => [null, null, ''];
        yield 'identifiant zero' => [0, 'Favorable', ''];
        yield 'libelle null' => [1, null, ''];
        yield 'libelle vide' => [1, '', ''];
        yield 'libelle zero conserve' => [1, '0', '0'];
        yield 'renseigne' => [1, 'Favorable', 'Favorable'];
    }

    private function attendreDocumentsManquants(): void
    {
        $this->connection->expects($this->once())->method('fetchAssociative')
            ->with($this->stringContains('FROM dossierdocmanquant'), ['id' => 42])
            ->willReturn(false);
    }

    /** @param array<string, string|null> $rawDossier */
    private function remplirDossier(Dossier $dossier, array $rawDossier = [], ?string $numInsee = null): void
    {
        new \ReflectionMethod(DossierRapportOdtService::class, 'fillChampsDossier')->invoke(
            $this->service,
            $this->odf,
            $dossier,
            $rawDossier + ['HORSDELAI_DOSSIER' => '1'],
            42,
            'Nature issue du SQL conserve',
            $numInsee
        );
        $this->assertSame('Oui', $this->champs['horsDelai']);
        $this->assertSame('Indisponible', $this->champs['date_commEntete']);
    }
}
