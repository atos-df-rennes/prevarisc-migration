<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Constant\TypeChamp as TypeChampConstant;
use App\Entity\Champ;
use App\Entity\Rubrique;
use App\Entity\TypeChamp;
use App\Entity\Valeur;
use App\Service\DescriptifInspector;
use PHPUnit\Framework\TestCase;

/**
 * Teste la détection de données saisies dans un descriptif.
 *
 * Couvre :
 * - Rubriques vides (aucune valeur)
 * - Champ simple avec valeur / sans valeur
 * - Champ checkbox : true = donnée, false = pas de donnée, null = pas de donnée
 * - Champ parent non-tableau : détection via sous_champs (y compris checkbox)
 * - Champ parent tableau : détection via lignes
 * - Combinaisons : premier champ vide, second champ renseigné
 */
final class DescriptifInspectorTest extends TestCase
{
    private DescriptifInspector $service;

    protected function setUp(): void
    {
        $this->service = new DescriptifInspector();
    }

    // -------------------------------------------------------------------------
    // Aucune donnée
    // -------------------------------------------------------------------------

    public function testRubriquesVidesRetourneFalse(): void
    {
        $this->assertFalse($this->service->rubriquesHaveData([]));
    }

    public function testChampSimpleSansValeurRetourneFalse(): void
    {
        $rubriques = [
            $this->buildRubriqueItem([
                $this->buildChampData(TypeChampConstant::TEXT, null),
            ]),
        ];

        $this->assertFalse($this->service->rubriquesHaveData($rubriques));
    }

    public function testCheckboxFalseRetourneFalse(): void
    {
        $valeur = new Valeur();
        $valeur->setCheckbox(false);

        $rubriques = [
            $this->buildRubriqueItem([
                $this->buildChampData(TypeChampConstant::CHECKBOX, $valeur),
            ]),
        ];

        $this->assertFalse($this->service->rubriquesHaveData($rubriques));
    }

    public function testCheckboxNullRetourneFalse(): void
    {
        $rubriques = [
            $this->buildRubriqueItem([
                $this->buildChampData(TypeChampConstant::CHECKBOX, null),
            ]),
        ];

        $this->assertFalse($this->service->rubriquesHaveData($rubriques));
    }

    public function testParentNonTableauSansSousChampValeurRetourneFalse(): void
    {
        $sousChamp = $this->buildChamp(TypeChampConstant::TEXT);

        $champData = $this->buildParentNonTableauChampData([
            ['champ' => $sousChamp, 'valeur' => null],
        ]);

        $this->assertFalse($this->service->rubriquesHaveData([
            $this->buildRubriqueItem([$champData]),
        ]));
    }

    public function testParentNonTableauAvecSousCheckboxFalseRetourneFalse(): void
    {
        $valeur = new Valeur();
        $valeur->setCheckbox(false);

        $sousChamp = $this->buildChamp(TypeChampConstant::CHECKBOX);

        $champData = $this->buildParentNonTableauChampData([
            ['champ' => $sousChamp, 'valeur' => $valeur],
        ]);

        $this->assertFalse($this->service->rubriquesHaveData([
            $this->buildRubriqueItem([$champData]),
        ]));
    }

    public function testParentTableauSansLignesRetourneFalse(): void
    {
        $champData = $this->buildParentTableauChampData([]);

        $this->assertFalse($this->service->rubriquesHaveData([
            $this->buildRubriqueItem([$champData]),
        ]));
    }

    // -------------------------------------------------------------------------
    // Données présentes
    // -------------------------------------------------------------------------

    public function testChampSimpleAvecValeurRetourneTrue(): void
    {
        $valeur = new Valeur();
        $valeur->setStr('Valeur saisie');

        $rubriques = [
            $this->buildRubriqueItem([
                $this->buildChampData(TypeChampConstant::TEXT, $valeur),
            ]),
        ];

        $this->assertTrue($this->service->rubriquesHaveData($rubriques));
    }

    public function testCheckboxTrueRetourneTrue(): void
    {
        $valeur = new Valeur();
        $valeur->setCheckbox(true);

        $rubriques = [
            $this->buildRubriqueItem([
                $this->buildChampData(TypeChampConstant::CHECKBOX, $valeur),
            ]),
        ];

        $this->assertTrue($this->service->rubriquesHaveData($rubriques));
    }

    public function testParentNonTableauAvecSousChampRenseigneRetourneTrue(): void
    {
        $valeur = new Valeur();
        $valeur->setStr('Présent');

        $sousChamp = $this->buildChamp(TypeChampConstant::TEXT);

        $champData = $this->buildParentNonTableauChampData([
            ['champ' => $sousChamp, 'valeur' => $valeur],
        ]);

        $this->assertTrue($this->service->rubriquesHaveData([
            $this->buildRubriqueItem([$champData]),
        ]));
    }

    public function testParentNonTableauAvecSousCheckboxTrueRetourneTrue(): void
    {
        $valeur = new Valeur();
        $valeur->setCheckbox(true);

        $sousChamp = $this->buildChamp(TypeChampConstant::CHECKBOX);

        $champData = $this->buildParentNonTableauChampData([
            ['champ' => $sousChamp, 'valeur' => $valeur],
        ]);

        $this->assertTrue($this->service->rubriquesHaveData([
            $this->buildRubriqueItem([$champData]),
        ]));
    }

    public function testParentTableauAvecLignesRetourneTrue(): void
    {
        $valeur = new Valeur();
        $valeur->setStr('ligne 1');

        $champData = $this->buildParentTableauChampData([[1 => $valeur]]);

        $this->assertTrue($this->service->rubriquesHaveData([
            $this->buildRubriqueItem([$champData]),
        ]));
    }

    // -------------------------------------------------------------------------
    // Court-circuit : premier champ vide, second renseigné
    // -------------------------------------------------------------------------

    public function testPremierChampVideSecondRenseigneRetourneTrue(): void
    {
        $valeur = new Valeur();
        $valeur->setNumber(42);

        $rubriques = [
            $this->buildRubriqueItem([
                $this->buildChampData(TypeChampConstant::TEXT, null),
                $this->buildChampData(TypeChampConstant::NUMBER, $valeur),
            ]),
        ];

        $this->assertTrue($this->service->rubriquesHaveData($rubriques));
    }

    public function testPremiereRubriqueVideSecondeRenseigneeRetourneTrue(): void
    {
        $valeur = new Valeur();
        $valeur->setStr('Renseigné');

        $rubriques = [
            $this->buildRubriqueItem([
                $this->buildChampData(TypeChampConstant::TEXT, null),
            ]),
            $this->buildRubriqueItem([
                $this->buildChampData(TypeChampConstant::TEXT, $valeur),
            ]),
        ];

        $this->assertTrue($this->service->rubriquesHaveData($rubriques));
    }

    // -------------------------------------------------------------------------
    // Builders
    // -------------------------------------------------------------------------

    /**
     * @param list<array<string, mixed>> $champs
     *
     * @return array{rubrique: Rubrique, display: bool, champs: list<array<string, mixed>>}
     */
    private function buildRubriqueItem(array $champs): array
    {
        return ['rubrique' => new Rubrique(), 'display' => true, 'opened' => true, 'champs' => $champs];
    }

    private function buildChamp(int $typeId): Champ
    {
        $type = new TypeChamp();
        $ref = new \ReflectionProperty(TypeChamp::class, 'id');
        $ref->setValue($type, $typeId);

        $champ = new Champ();
        $champ->setType($type);

        return $champ;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildChampData(int $typeId, ?Valeur $valeur): array
    {
        return ['champ' => $this->buildChamp($typeId), 'valeur' => $valeur];
    }

    /**
     * @param list<array<string, mixed>> $sousChamps
     *
     * @return array<string, mixed>
     */
    private function buildParentNonTableauChampData(array $sousChamps): array
    {
        return [
            'champ' => $this->buildChamp(TypeChampConstant::PARENT),
            'valeur' => null,
            'sous_champs' => $sousChamps,
        ];
    }

    /**
     * @param list<array<int, Valeur>> $lignes
     *
     * @return array<string, mixed>
     */
    private function buildParentTableauChampData(array $lignes): array
    {
        return [
            'champ' => $this->buildChamp(TypeChampConstant::PARENT),
            'valeur' => null,
            'lignes' => $lignes,
        ];
    }
}
