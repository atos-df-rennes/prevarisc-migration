<?php

namespace App\Tests\Service\Descriptif;

use App\Constant\TypeChamp;
use App\Dto\Descriptif\ChampValueData;
use App\Dto\Descriptif\TableauRowData;
use App\Entity\Champ;
use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Entity\Rubrique;
use App\Entity\TypeChamp as TypeChampEntity;
use App\Entity\Valeur;
use App\Service\Descriptif\DescriptifFormHydrator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DescriptifFormHydrator::class)]
class DescriptifFormHydratorTest extends TestCase
{
    private DescriptifFormHydrator $hydrator;

    protected function setUp(): void
    {
        $this->hydrator = new DescriptifFormHydrator();
    }

    public function testHydrateChampSimpleTexte(): void
    {
        $champ = $this->makeChamp(1, TypeChamp::TEXT);
        $valeur = $this->makeValeur(10);
        $valeur->setStr('valeur test');

        $data = $this->hydrateSimple($champ, $valeur);

        self::assertSame('valeur test', $data->getValue());
        self::assertSame(10, $data->getValeurId());
        self::assertSame(1, $data->getChampId());
    }

    public function testHydrateChampSimpleSansValeur(): void
    {
        $champ = $this->makeChamp(2, TypeChamp::TEXT);

        $data = $this->hydrateSimple($champ, null);

        self::assertNull($data->getValue());
        self::assertNull($data->getValeurId());
    }

    public function testHydrateChampCheckboxVrai(): void
    {
        $champ = $this->makeChamp(3, TypeChamp::CHECKBOX);
        $valeur = $this->makeValeur(20);
        $valeur->setCheckbox(true);

        $data = $this->hydrateSimple($champ, $valeur);

        self::assertTrue($data->getValue());
    }

    public function testHydrateChampCheckboxFaux(): void
    {
        $champ = $this->makeChamp(4, TypeChamp::CHECKBOX);
        $valeur = $this->makeValeur(21);
        $valeur->setCheckbox(false);

        $data = $this->hydrateSimple($champ, $valeur);

        self::assertFalse($data->getValue());
    }

    public function testHydrateChampDate(): void
    {
        $champ = $this->makeChamp(5, TypeChamp::DATE);
        $valeur = $this->makeValeur(30);
        $date = new \DateTime('2025-06-15');
        $valeur->setDate($date);

        $data = $this->hydrateSimple($champ, $valeur);

        self::assertInstanceOf(\DateTimeInterface::class, $data->getValue());
        self::assertSame('15/06/2025', $data->getValue()->format('d/m/Y'));
    }

    public function testHydrateChampNumber(): void
    {
        $champ = $this->makeChamp(6, TypeChamp::NUMBER);
        $valeur = $this->makeValeur(40);
        $valeur->setNumber(42);

        $data = $this->hydrateSimple($champ, $valeur);

        self::assertSame(42, $data->getValue());
    }

    public function testHydrateChampSelect(): void
    {
        $champ = $this->makeChamp(7, TypeChamp::SELECT);
        $valeur = $this->makeValeur(50);
        $valeur->setStr('Option A');

        $data = $this->hydrateSimple($champ, $valeur);

        self::assertSame('Option A', $data->getValue());
    }

    public function testHydrateTableauAvecLignesExistantes(): void
    {
        $parentChamp = $this->makeChamp(10, TypeChamp::PARENT, true);
        $enfant1 = $this->makeChamp(11, TypeChamp::TEXT);
        $enfant2 = $this->makeChamp(12, TypeChamp::NUMBER);

        $valeur1 = $this->makeValeur(100);
        $valeur1->setStr('abc');
        $valeur1->setChamp($enfant1);

        $valeur2 = $this->makeValeur(101);
        $valeur2->setNumber(99);
        $valeur2->setChamp($enfant2);

        $lignes = [
            [11 => $valeur1, 12 => $valeur2],
        ];

        $data = $this->hydrateTableauChamp($parentChamp, $lignes);

        self::assertCount(1, $data->getLignes());

        $row = $data->getLignes()[0];
        self::assertInstanceOf(TableauRowData::class, $row);
        self::assertSame('abc', $row->getCell(11));
        self::assertSame(99, $row->getCell(12));
        self::assertSame(100, $row->getValeurId(11));
        self::assertSame(101, $row->getValeurId(12));
    }

    public function testHydrateTableauVide(): void
    {
        $parentChamp = $this->makeChamp(13, TypeChamp::PARENT, true);

        $data = $this->hydrateTableauChamp($parentChamp, []);

        self::assertCount(0, $data->getLignes());
    }

    public function testHydrateRubriqueAvecDisplayFalse(): void
    {
        $champ = $this->makeChamp(20, TypeChamp::TEXT);
        $valeur = $this->makeValeur(200);
        $valeur->setStr('hello');

        $rubrique = $this->makeRubrique(5);

        $rubriques = [
            [
                'rubrique' => $rubrique,
                'display' => false,
                'champs' => [
                    ['champ' => $champ, 'valeur' => $valeur],
                ],
            ],
        ];

        $dossier = $this->createMock(Dossier::class);
        $formData = $this->hydrator->hydrateFromDossier($dossier, $rubriques);

        $rubriqueData = $formData->getRubrique(5);
        self::assertNotNull($rubriqueData);
        self::assertFalse($rubriqueData->isDisplay());
    }

    public function testHydrateRubriquesVides(): void
    {
        $dossier = $this->createMock(Dossier::class);
        $formData = $this->hydrator->hydrateFromDossier($dossier, []);

        self::assertEmpty($formData->getRubriques());
    }

    public function testHydrateDepuisEtablissement(): void
    {
        $champ = $this->makeChamp(30, TypeChamp::TEXT);
        $valeur = $this->makeValeur(300);
        $valeur->setStr('ets value');

        $rubrique = $this->makeRubrique(9);
        $rubriques = [
            [
                'rubrique' => $rubrique,
                'display' => true,
                'champs' => [['champ' => $champ, 'valeur' => $valeur]],
            ],
        ];

        $etablissement = $this->createMock(Etablissement::class);
        $formData = $this->hydrator->hydrateFromEtablissement($etablissement, $rubriques);

        $rubriqueData = $formData->getRubrique(9);
        self::assertNotNull($rubriqueData);
        $champData = $rubriqueData->getChamp(30);
        self::assertNotNull($champData);
        self::assertSame('ets value', $champData->getValue());
    }

    // --- Helpers ---

    private function hydrateSimple(Champ $champ, ?Valeur $valeur): ChampValueData
    {
        $rubrique = $this->makeRubrique(1);
        $rubriques = [
            [
                'rubrique' => $rubrique,
                'display' => true,
                'champs' => [
                    ['champ' => $champ, 'valeur' => $valeur],
                ],
            ],
        ];

        $dossier = $this->createMock(Dossier::class);
        $formData = $this->hydrator->hydrateFromDossier($dossier, $rubriques);

        $rubriqueData = $formData->getRubrique(1);
        self::assertNotNull($rubriqueData);
        $champData = $rubriqueData->getChamp($champ->getId());
        self::assertNotNull($champData);

        return $champData;
    }

    /**
     * @param list<array<int, Valeur>> $lignes
     */
    private function hydrateTableauChamp(Champ $parentChamp, array $lignes): ChampValueData
    {
        $rubrique = $this->makeRubrique(1);
        $rubriques = [
            [
                'rubrique' => $rubrique,
                'display' => true,
                'champs' => [
                    ['champ' => $parentChamp, 'valeur' => null, 'lignes' => $lignes],
                ],
            ],
        ];

        $dossier = $this->createMock(Dossier::class);
        $formData = $this->hydrator->hydrateFromDossier($dossier, $rubriques);

        $rubriqueData = $formData->getRubrique(1);
        self::assertNotNull($rubriqueData);
        $champData = $rubriqueData->getChamp($parentChamp->getId());
        self::assertNotNull($champData);

        return $champData;
    }

    private function makeChamp(int $id, int $typeId, bool $tableau = false): Champ
    {
        $typeEntity = $this->createMock(TypeChampEntity::class);
        $typeEntity->method('getId')->willReturn($typeId);

        $champ = $this->createMock(Champ::class);
        $champ->method('getId')->willReturn($id);
        $champ->method('getType')->willReturn($typeEntity);
        $champ->method('getTableau')->willReturn($tableau);

        return $champ;
    }

    private function makeValeur(int $id): Valeur
    {
        $valeur = new Valeur();
        // Pas de setter pour l'ID en général dans Doctrine (clé auto), on utilise la réflexion
        $ref = new \ReflectionClass($valeur);
        $prop = $ref->getProperty('id');
        $prop->setValue($valeur, $id);

        return $valeur;
    }

    private function makeRubrique(int $id): Rubrique
    {
        $rubrique = $this->createMock(Rubrique::class);
        $rubrique->method('getId')->willReturn($id);

        return $rubrique;
    }
}
