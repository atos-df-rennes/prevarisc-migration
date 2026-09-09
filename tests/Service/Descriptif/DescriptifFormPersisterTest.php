<?php

namespace App\Tests\Service\Descriptif;

use PHPUnit\Framework\MockObject\Stub;
use App\Constant\TypeChamp;
use App\Dto\Descriptif\ChampValueData;
use App\Dto\Descriptif\DescriptifFormData;
use App\Dto\Descriptif\RubriqueFormData;
use App\Dto\Descriptif\TableauRowData;
use App\Entity\Champ;
use App\Entity\Dossier;
use App\Entity\DossierDisplayRubrique;
use App\Entity\Rubrique;
use App\Entity\TypeChamp as TypeChampEntity;
use App\Entity\Valeur;
use App\Repository\ChampRepository;
use App\Repository\DossierDisplayRubriqueRepository;
use App\Repository\EtablissementDisplayRubriqueRepository;
use App\Repository\ValeurRepository;
use App\Service\Descriptif\DescriptifFormPersister;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(DescriptifFormPersister::class)]
class DescriptifFormPersisterTest extends TestCase
{
    /** @var MockObject&ValeurRepository */
    private MockObject $valeurRepository;

    /** @var MockObject&ChampRepository */
    private MockObject $champRepository;

    /** @var MockObject&DossierDisplayRubriqueRepository */
    private MockObject $dossierDisplayRubriqueRepository;

    /** @var Stub&EtablissementDisplayRubriqueRepository */
    private Stub $etablissementDisplayRubriqueRepository;

    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManager;

    private DescriptifFormPersister $persister;

    protected function setUp(): void
    {
        $this->valeurRepository = $this->createMock(ValeurRepository::class);
        $this->champRepository = $this->createMock(ChampRepository::class);
        $this->dossierDisplayRubriqueRepository = $this->createMock(DossierDisplayRubriqueRepository::class);
        $this->etablissementDisplayRubriqueRepository = $this->createStub(EtablissementDisplayRubriqueRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->persister = new DescriptifFormPersister(
            $this->valeurRepository,
            $this->champRepository,
            $this->dossierDisplayRubriqueRepository,
            $this->etablissementDisplayRubriqueRepository,
            $this->entityManager
        );
    }

    public function testPersistChampSimpleMetAJour(): void
    {
        $champ = $this->makeChamp(1, TypeChamp::TEXT);
        $existing = new Valeur();
        $existing->setStr('ancienne valeur');
        $existing->setChamp($champ);
        $existing->setIdx(null);

        // findByDossier retourne array<champId, Valeur[]>
        $this->valeurRepository
            ->method('findByDossier')
            ->willReturn([1 => [$existing]]);

        $this->champRepository
            ->method('find')
            ->willReturn($champ);

        $this->dossierDisplayRubriqueRepository
            ->method('findOneBy')
            ->willReturn(null);

        $this->entityManager->expects(self::once())->method('flush');

        $dossier = $this->makeDossier([$existing]);
        $rubriques = $this->makeRubriquesStructure(10, [['champ' => $champ, 'valeur' => $existing]]);

        $champValueData = new ChampValueData(1, 'nouvelle valeur', [], [], null);
        $formData = $this->makeFormData(10, true, [1 => $champValueData]);

        $this->persister->persistForDossier($dossier, $formData, $rubriques);

        self::assertSame('nouvelle valeur', $existing->getStr());
    }

    public function testPersistChampSimpleCree(): void
    {
        $champ = $this->makeChamp(2, TypeChamp::TEXT);

        $this->valeurRepository
            ->method('findByDossier')
            ->willReturn([]);

        $this->champRepository
            ->method('find')
            ->with(2)
            ->willReturn($champ);

        $this->dossierDisplayRubriqueRepository
            ->method('findOneBy')
            ->willReturn(null);

        $persisted = null;
        $this->entityManager
            ->expects(self::atLeastOnce())
            ->method('persist')
            ->willReturnCallback(static function ($obj) use (&$persisted): void {
                $persisted = $obj;
            });
        $this->entityManager->expects(self::once())->method('flush');

        $collection = new ArrayCollection();
        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getValeurs')->willReturn($collection);
        $dossier->method('getId')->willReturn(1);

        $rubriques = $this->makeRubriquesStructure(10, [['champ' => $champ, 'valeur' => null]]);
        $champValueData = new ChampValueData(2, 'nouvelle valeur');
        $formData = $this->makeFormData(10, true, [2 => $champValueData]);

        $this->persister->persistForDossier($dossier, $formData, $rubriques);

        self::assertInstanceOf(Valeur::class, $persisted);
        self::assertSame('nouvelle valeur', $persisted->getStr());
    }

    public function testPersistChampSimpleVideSupprime(): void
    {
        $champ = $this->makeChamp(3, TypeChamp::TEXT);
        $existing = new Valeur();
        $existing->setStr('a supprimer');
        $existing->setChamp($champ);
        $existing->setIdx(null);

        // findByDossier retourne array<champId, Valeur[]>
        $this->valeurRepository
            ->method('findByDossier')
            ->willReturn([3 => [$existing]]);

        $this->champRepository
            ->method('find')
            ->with(3)
            ->willReturn($champ);

        $this->dossierDisplayRubriqueRepository
            ->method('findOneBy')
            ->willReturn(null);

        $removed = null;
        $this->entityManager
            ->expects(self::once())
            ->method('remove')
            ->willReturnCallback(static function ($obj) use (&$removed): void {
                $removed = $obj;
            });
        $this->entityManager->expects(self::once())->method('flush');

        $collection = new ArrayCollection([$existing]);
        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getValeurs')->willReturn($collection);
        $dossier->method('getId')->willReturn(1);

        $rubriques = $this->makeRubriquesStructure(10, [['champ' => $champ, 'valeur' => $existing]]);
        $champValueData = new ChampValueData(3, null); // valeur vide → suppression
        $formData = $this->makeFormData(10, true, [3 => $champValueData]);

        $this->persister->persistForDossier($dossier, $formData, $rubriques);

        self::assertSame($existing, $removed);
    }

    public function testPersistTableauNouvelleLigne(): void
    {
        $enfantChamp = $this->makeChamp(11, TypeChamp::TEXT);
        $parentChamp = $this->makeChamp(10, TypeChamp::PARENT, true, [$enfantChamp]);

        $this->valeurRepository
            ->method('findByDossier')
            ->willReturn([]);

        $this->champRepository
            ->method('find')
            ->willReturnCallback(function ($id) use ($parentChamp, $enfantChamp): ?Champ {
                if (10 === $id) {
                    return $parentChamp;
                }

                if (11 === $id) {
                    return $enfantChamp;
                }

                return null;
            });

        $this->dossierDisplayRubriqueRepository
            ->method('findOneBy')
            ->willReturn(null);

        $persisted = [];
        $this->entityManager
            ->method('persist')
            ->willReturnCallback(static function ($obj) use (&$persisted): void {
                $persisted[] = $obj;
            });
        $this->entityManager->expects(self::once())->method('flush');

        $collection = new ArrayCollection();
        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getValeurs')->willReturn($collection);
        $dossier->method('getId')->willReturn(1);

        $newRow = new TableauRowData([11 => 'ligne1']);
        $parentData = new ChampValueData(10, null, [$newRow]);
        $rubriques = $this->makeRubriquesStructure(10, [['champ' => $parentChamp, 'valeur' => null, 'lignes' => []]]);
        $formData = $this->makeFormData(10, true, [10 => $parentData]);

        $this->persister->persistForDossier($dossier, $formData, $rubriques);

        $valeurs = array_filter($persisted, static fn ($obj): bool => $obj instanceof Valeur);
        self::assertNotEmpty($valeurs);

        $created = array_first($valeurs);
        self::assertInstanceOf(Valeur::class, $created);
        self::assertSame('ligne1', $created->getStr());
        self::assertSame(1, $created->getIdx()); // idx = position 1-based
    }

    public function testPersistTableauSupprimeLigneAbsente(): void
    {
        $enfantChamp = $this->makeChamp(11, TypeChamp::TEXT);
        $parentChamp = $this->makeChamp(10, TypeChamp::PARENT, true, [$enfantChamp]);

        // Ligne existante en base (idx=1)
        $existingValeur = new Valeur();
        $existingValeur->setStr('ligne a supprimer');
        $existingValeur->setChamp($enfantChamp);
        $existingValeur->setIdx(1);
        $this->setId($existingValeur, 999);

        // findByDossier retourne array<champId, Valeur[]>
        $this->valeurRepository
            ->method('findByDossier')
            ->willReturn([11 => [$existingValeur]]);

        $this->champRepository
            ->method('find')
            ->willReturnCallback(function ($id) use ($parentChamp, $enfantChamp): ?Champ {
                if (10 === $id) {
                    return $parentChamp;
                }

                if (11 === $id) {
                    return $enfantChamp;
                }

                return null;
            });

        $this->dossierDisplayRubriqueRepository
            ->method('findOneBy')
            ->willReturn(null);

        $removed = [];
        $this->entityManager
            ->method('remove')
            ->willReturnCallback(static function ($obj) use (&$removed): void {
                $removed[] = $obj;
            });
        $this->entityManager->expects(self::once())->method('flush');

        $collection = new ArrayCollection([$existingValeur]);
        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getValeurs')->willReturn($collection);
        $dossier->method('getId')->willReturn(1);

        // Formulaire soumis sans lignes (tableau vide) → l'existante doit être supprimée
        $parentData = new ChampValueData(10, null, []);
        $rubriques = $this->makeRubriquesStructure(10, [['champ' => $parentChamp, 'valeur' => null, 'lignes' => []]]);
        $formData = $this->makeFormData(10, true, [10 => $parentData]);

        $this->persister->persistForDossier($dossier, $formData, $rubriques);

        self::assertContains($existingValeur, $removed);
    }

    public function testPersistTableauNeSupprimesPasValeursDAutreOnglet(): void
    {
        // Champs de l'onglet soumis (ex: "Descriptif technique")
        $enfantChamp1 = $this->makeChamp(11, TypeChamp::TEXT);
        $parentChamp1 = $this->makeChamp(10, TypeChamp::PARENT, true, [$enfantChamp1]);

        // Champs d'un autre onglet (ex: "Effectifs et dégagements") — non soumis
        $enfantChamp2 = $this->makeChamp(21, TypeChamp::TEXT);

        // Valeur tableau existante appartenant à l'autre onglet
        $valeurAutreOnglet = new Valeur();
        $valeurAutreOnglet->setStr('valeur autre onglet');
        $valeurAutreOnglet->setChamp($enfantChamp2);
        $valeurAutreOnglet->setIdx(1);
        $this->setId($valeurAutreOnglet, 888);

        // findByDossier retourne les valeurs des deux onglets
        $this->valeurRepository
            ->method('findByDossier')
            ->willReturn([21 => [$valeurAutreOnglet]]);

        $this->champRepository
            ->method('find')
            ->willReturnCallback(function ($id) use ($parentChamp1, $enfantChamp1): ?Champ {
                if (10 === $id) {
                    return $parentChamp1;
                }

                if (11 === $id) {
                    return $enfantChamp1;
                }

                return null;
            });

        $this->dossierDisplayRubriqueRepository
            ->method('findOneBy')
            ->willReturn(null);

        $removed = [];
        $this->entityManager
            ->method('remove')
            ->willReturnCallback(static function ($obj) use (&$removed): void {
                $removed[] = $obj;
            });
        $this->entityManager->expects(self::once())->method('flush');

        $collection = new ArrayCollection([$valeurAutreOnglet]);
        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getValeurs')->willReturn($collection);
        $dossier->method('getId')->willReturn(1);

        // Soumettre uniquement l'onglet 1 avec une nouvelle ligne
        $newRow = new TableauRowData([11 => 'nouvelle ligne']);
        $parentData = new ChampValueData(10, null, [$newRow]);
        $rubriques = $this->makeRubriquesStructure(10, [['champ' => $parentChamp1, 'valeur' => null, 'lignes' => []]]);
        $formData = $this->makeFormData(10, true, [10 => $parentData]);

        $this->persister->persistForDossier($dossier, $formData, $rubriques);

        // La valeur de l'autre onglet ne doit PAS être supprimée
        self::assertNotContains($valeurAutreOnglet, $removed);
    }

    public function testPersistDisplayPrefCree(): void
    {
        $champ = $this->makeChamp(1, TypeChamp::TEXT);

        $this->valeurRepository->method('findByDossier')->willReturn([]);
        $this->champRepository->method('find')->willReturn($champ);

        // Pas de préférence existante → doit appeler persist avec un DisplayRubrique
        $this->dossierDisplayRubriqueRepository
            ->method('findOneBy')
            ->willReturn(null);

        $persisted = [];
        $this->entityManager
            ->method('persist')
            ->willReturnCallback(static function ($obj) use (&$persisted): void {
                $persisted[] = $obj;
            });
        $this->entityManager->expects(self::once())->method('flush');

        $collection = new ArrayCollection();
        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getValeurs')->willReturn($collection);
        $dossier->method('getId')->willReturn(1);

        $champValueData = new ChampValueData(1, null); // valeur vide
        $formData = $this->makeFormData(10, false, [1 => $champValueData]); // display=false

        $rubriques = $this->makeRubriquesStructure(10, [['champ' => $champ, 'valeur' => null]]);

        $this->persister->persistForDossier($dossier, $formData, $rubriques);

        // Un objet DossierDisplayRubrique doit avoir été persisté
        $displayPrefs = array_filter($persisted, static fn ($obj): bool => $obj instanceof DossierDisplayRubrique);
        self::assertNotEmpty($displayPrefs);
    }

    // --- Helpers ---

    /**
     * @param Champ[] $enfants
     */
    private function makeChamp(int $id, int $typeId, bool $tableau = false, array $enfants = []): Champ
    {
        $typeEntity = $this->createMock(TypeChampEntity::class);
        $typeEntity->method('getId')->willReturn($typeId);

        $champ = $this->createMock(Champ::class);
        $champ->method('getId')->willReturn($id);
        $champ->method('getType')->willReturn($typeEntity);
        $champ->method('getTableau')->willReturn($tableau);
        $champ->method('getEnfants')->willReturn(new ArrayCollection($enfants));

        return $champ;
    }

    private function makeRubrique(int $id): Rubrique
    {
        $rubrique = $this->createMock(Rubrique::class);
        $rubrique->method('getId')->willReturn($id);

        return $rubrique;
    }

    private function makeDossier(array $valeurs = []): Dossier
    {
        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getValeurs')->willReturn(new ArrayCollection($valeurs));
        $dossier->method('getId')->willReturn(1);

        return $dossier;
    }

    /**
     * @param array<int, ChampValueData> $champs
     */
    private function makeFormData(int $rubriqueId, bool $display, array $champs): DescriptifFormData
    {
        $rubriqueData = new RubriqueFormData($display, $champs);

        return new DescriptifFormData([$rubriqueId => $rubriqueData]);
    }

    /**
     * @param list<array<string, mixed>> $champs
     *
     * @return list<array<string, mixed>>
     */
    private function makeRubriquesStructure(int $rubriqueId, array $champs): array
    {
        return [
            [
                'rubrique' => $this->makeRubrique($rubriqueId),
                'display' => true,
                'champs' => $champs,
            ],
        ];
    }

    private function setId(Valeur $valeur, int $id): void
    {
        $ref = new \ReflectionClass($valeur);
        $prop = $ref->getProperty('id');
        $prop->setValue($valeur, $id);
    }
}
