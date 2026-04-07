<?php

namespace App\Tests\Service;

use App\Constant\TypeChamp as TypeChampConstant;
use App\Entity\Champ;
use App\Entity\Dossier;
use App\Entity\DossierDisplayRubrique;
use App\Entity\Etablissement;
use App\Entity\Rubrique;
use App\Entity\TypeChamp;
use App\Entity\Valeur;
use App\Repository\ChampRepository;
use App\Repository\DossierDisplayRubriqueRepository;
use App\Repository\EtablissementDisplayRubriqueRepository;
use App\Repository\RubriqueRepository;
use App\Repository\ValeurRepository;
use App\Service\DescriptifSaver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Teste la logique de sauvegarde du service DescriptifSaver.
 *
 * Couvre :
 * - Champ simple : mise à jour d'une valeur existante
 * - Champ simple : création d'une nouvelle valeur
 * - Valeur vide sur existant : suppression
 * - Valeur vide sur nouveau : skip (pas d'insert)
 * - Checkbox : persistance de false ('0') et true ('1')
 * - Date : parsing du format 'd/m/Y'
 * - Nouvelle ligne de tableau : insert avec idx calculé
 * - Suppression d'une ligne de tableau absente du POST
 * - Préférence d'affichage rubrique : upsert (create)
 * - Préférence d'affichage rubrique : upsert (update)
 * - Clé POST malformée : ignorée silencieusement
 */
final class DescriptifSaverTest extends TestCase
{
    /** @var ValeurRepository|MockObject */
    private $valeurRepository;

    /** @var ChampRepository|MockObject */
    private $champRepository;

    /** @var RubriqueRepository|MockObject */
    private $rubriqueRepository;

    /** @var DossierDisplayRubriqueRepository|MockObject */
    private $dossierDisplayRubriqueRepository;

    /** @var EtablissementDisplayRubriqueRepository|MockObject */
    private $etablissementDisplayRubriqueRepository;

    /** @var EntityManagerInterface|MockObject */
    private $entityManager;

    /** @var DescriptifSaver */
    private $service;

    protected function setUp(): void
    {
        $this->valeurRepository = $this->createMock(ValeurRepository::class);
        $this->champRepository = $this->createMock(ChampRepository::class);
        $this->rubriqueRepository = $this->createMock(RubriqueRepository::class);
        $this->dossierDisplayRubriqueRepository = $this->createMock(DossierDisplayRubriqueRepository::class);
        $this->etablissementDisplayRubriqueRepository = $this->createMock(EtablissementDisplayRubriqueRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->service = new DescriptifSaver(
            $this->valeurRepository,
            $this->champRepository,
            $this->rubriqueRepository,
            $this->dossierDisplayRubriqueRepository,
            $this->etablissementDisplayRubriqueRepository,
            $this->entityManager
        );
    }

    public function testChampSimpleMetAJourValeurExistante(): void
    {
        $champ = $this->buildChamp(10, TypeChampConstant::TEXT);
        $valeur = $this->buildValeur(1, $champ, null);
        $valeur->setStr('ancienne valeur');

        $dossier = new Dossier();
        $dossier->getValeurs()->add($valeur);
        $this->valeurRepository->method('findByDossier')->willReturn([10 => [$valeur]]);

        $this->entityManager->expects(self::once())->method('flush');
        $this->entityManager->expects(self::never())->method('persist');

        $this->service->saveForDossier($dossier, ['champ-10' => 'nouvelle valeur']);

        self::assertSame('nouvelle valeur', $valeur->getStr());
    }

    public function testChampSimpleCreerNouvelleValeur(): void
    {
        $champ = $this->buildChamp(10, TypeChampConstant::TEXT);
        $dossier = new Dossier();

        $this->valeurRepository->method('findByDossier')->willReturn([]);
        $this->champRepository->method('find')->with(10)->willReturn($champ);

        $this->entityManager->expects(self::once())->method('persist');
        $this->entityManager->expects(self::once())->method('flush');

        $this->service->saveForDossier($dossier, ['champ-10' => 'valeur initiale']);

        $valeurs = $dossier->getValeurs()->toArray();
        self::assertCount(1, $valeurs);
        self::assertSame('valeur initiale', $valeurs[0]->getStr());
    }

    public function testChampSimpleVideSupprimeLaValeurExistante(): void
    {
        $champ = $this->buildChamp(10, TypeChampConstant::TEXT);
        $valeur = $this->buildValeur(1, $champ, null);
        $valeur->setStr('à supprimer');

        $dossier = new Dossier();
        $dossier->getValeurs()->add($valeur);
        $this->valeurRepository->method('findByDossier')->willReturn([10 => [$valeur]]);

        $this->entityManager->expects(self::once())->method('remove')->with($valeur);
        $this->entityManager->expects(self::once())->method('flush');

        $this->service->saveForDossier($dossier, ['champ-10' => '']);

        self::assertCount(0, $dossier->getValeurs());
    }

    public function testChampSimpleVideNeCreePasDeNouvelleValeur(): void
    {
        $dossier = new Dossier();
        $this->valeurRepository->method('findByDossier')->willReturn([]);

        $this->entityManager->expects(self::never())->method('persist');
        $this->entityManager->expects(self::once())->method('flush');

        $this->service->saveForDossier($dossier, ['champ-10' => '']);

        self::assertCount(0, $dossier->getValeurs());
    }

    /**
     * @dataProvider checkboxProvider
     */
    public function testCheckboxPersisteLaValeur(string $rawValue, bool $expected): void
    {
        $champ = $this->buildChamp(20, TypeChampConstant::CHECKBOX);
        $valeur = $this->buildValeur(2, $champ, null);

        $dossier = new Dossier();
        $dossier->getValeurs()->add($valeur);
        $this->valeurRepository->method('findByDossier')->willReturn([20 => [$valeur]]);

        $this->service->saveForDossier($dossier, ['champ-20' => $rawValue]);

        self::assertSame($expected, $valeur->getCheckbox());
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public function checkboxProvider(): array
    {
        return [
            'coché (1)'   => ['1', true],
            'décoché (0)' => ['0', false],
        ];
    }

    public function testDateParseLeFormatFrancais(): void
    {
        $champ = $this->buildChamp(30, TypeChampConstant::DATE);
        $valeur = $this->buildValeur(3, $champ, null);

        $dossier = new Dossier();
        $dossier->getValeurs()->add($valeur);
        $this->valeurRepository->method('findByDossier')->willReturn([30 => [$valeur]]);

        $this->service->saveForDossier($dossier, ['champ-30' => '15/06/2025']);

        self::assertInstanceOf(\DateTime::class, $valeur->getDate());
        self::assertSame('15/06/2025', $valeur->getDate()->format('d/m/Y'));
    }

    public function testNouvelleLigneTableauInsereAvecIdx(): void
    {
        $champ = $this->buildChamp(50, TypeChampConstant::TEXT);
        $dossier = new Dossier();

        $this->valeurRepository->method('findByDossier')->willReturn([]);
        $this->champRepository->method('find')->with(50)->willReturn($champ);

        $this->entityManager->expects(self::once())->method('persist');
        $this->entityManager->expects(self::once())->method('flush');

        // Format : valeur-{timestamp}-{parentId}-{champId}-NULL
        $this->service->saveForDossier($dossier, ['valeur-1700000000-99-50-NULL' => 'cellule 1']);

        $valeurs = $dossier->getValeurs()->toArray();
        self::assertCount(1, $valeurs);
        self::assertSame(1, $valeurs[0]->getIdx());
        self::assertSame('cellule 1', $valeurs[0]->getStr());
    }

    public function testLigneTableauAbsenteDuPostEstSupprimee(): void
    {
        $champ = $this->buildChamp(50, TypeChampConstant::TEXT);
        $valeurExistante = $this->buildValeur(99, $champ, 1);
        $valeurExistante->setStr('ligne à supprimer');

        $dossier = new Dossier();
        $dossier->getValeurs()->add($valeurExistante);
        $this->valeurRepository->method('findByDossier')->willReturn([50 => [$valeurExistante]]);

        $this->entityManager->expects(self::once())->method('remove')->with($valeurExistante);
        $this->entityManager->expects(self::once())->method('flush');

        // POST vide : aucune ligne de tableau soumise
        $this->service->saveForDossier($dossier, []);

        self::assertCount(0, $dossier->getValeurs());
    }

    public function testAfficherRubriqueCreePrefSiAbsente(): void
    {
        $rubrique = new Rubrique();
        $dossier = new Dossier();

        $this->valeurRepository->method('findByDossier')->willReturn([]);
        $this->rubriqueRepository->method('find')->with(7)->willReturn($rubrique);
        $this->dossierDisplayRubriqueRepository->method('findOneBy')->willReturn(null);

        $this->entityManager->expects(self::once())->method('persist')
            ->with(self::isInstanceOf(DossierDisplayRubrique::class));
        $this->entityManager->expects(self::once())->method('flush');

        $this->service->saveForDossier($dossier, ['afficher_rubrique-7' => '1']);
    }

    public function testAfficherRubriqueMetAJourPrefExistante(): void
    {
        $rubrique = new Rubrique();
        $dossier = new Dossier();

        $pref = $this->createMock(DossierDisplayRubrique::class);
        $pref->expects(self::once())->method('setUserDisplay')->with(false);

        $this->valeurRepository->method('findByDossier')->willReturn([]);
        $this->rubriqueRepository->method('find')->with(7)->willReturn($rubrique);
        $this->dossierDisplayRubriqueRepository->method('findOneBy')->willReturn($pref);

        $this->entityManager->expects(self::never())->method('persist');
        $this->entityManager->expects(self::once())->method('flush');

        $this->service->saveForDossier($dossier, ['afficher_rubrique-7' => '0']);
    }

    public function testClePostMalformeeIgnoreeSilencieusement(): void
    {
        $dossier = new Dossier();
        $this->valeurRepository->method('findByDossier')->willReturn([]);

        $this->entityManager->expects(self::never())->method('persist');
        $this->entityManager->expects(self::never())->method('remove');
        $this->entityManager->expects(self::once())->method('flush');

        $this->service->saveForDossier($dossier, [
            'cle_inconnue'        => 'valeur',
            'valeur-1-2-3'        => 'trop peu de parties',
            'valeur-1-2-3-4-5-6'  => 'trop de parties',
        ]);
    }

    public function testSaveForEtablissementFonctionne(): void
    {
        $champ = $this->buildChamp(10, TypeChampConstant::TEXT);
        $etablissement = new Etablissement();

        $this->valeurRepository->method('findByEtablissement')->willReturn([]);
        $this->champRepository->method('find')->with(10)->willReturn($champ);

        $this->entityManager->expects(self::once())->method('persist');
        $this->entityManager->expects(self::once())->method('flush');

        $this->service->saveForEtablissement($etablissement, ['champ-10' => 'test établissement']);

        $valeurs = $etablissement->getValeurs()->toArray();
        self::assertCount(1, $valeurs);
        self::assertSame('test établissement', $valeurs[0]->getStr());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function setEntityId($entity, int $id): void
    {
        $ref = new \ReflectionProperty(\get_class($entity), 'id');
        $ref->setAccessible(true);
        $ref->setValue($entity, $id);
    }

    private function buildChamp(int $id, int $typeId): Champ
    {
        $type = new TypeChamp();
        $this->setEntityId($type, $typeId);

        $champ = new Champ();
        $this->setEntityId($champ, $id);
        $champ->setType($type);

        return $champ;
    }

    private function buildValeur(int $id, Champ $champ, ?int $idx): Valeur
    {
        $valeur = new Valeur();
        $this->setEntityId($valeur, $id);
        $valeur->setChamp($champ);
        $valeur->setIdx($idx);

        return $valeur;
    }
}
