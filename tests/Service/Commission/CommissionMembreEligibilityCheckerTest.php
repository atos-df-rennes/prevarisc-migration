<?php

namespace App\Tests\Service\Commission;

use App\Service\Commission\CommissionMembreEligibilityChecker;
use PHPUnit\Framework\TestCase;

class CommissionMembreEligibilityCheckerTest extends TestCase
{
    private CommissionMembreEligibilityChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new CommissionMembreEligibilityChecker();
    }

    public function testRetourneFalseSiPasDInformationsEtablissement(): void
    {
        $membre = $this->buildMembre();
        $dossier = ['TYPE_DOSSIER' => 1, 'infosEtab' => []];

        self::assertFalse($this->checker->isDossierConcerneMembre($membre, $dossier));
    }

    public function testRetourneFalseSiTypeDossierNonDansCompetences(): void
    {
        $membre = $this->buildMembre(['dossiertypes' => []]);
        $dossier = $this->buildDossier(['TYPE_DOSSIER' => 1]);

        self::assertFalse($this->checker->isDossierConcerneMembre($membre, $dossier));
    }

    public function testRetourneVraiSiGenreManifestation(): void
    {
        $membre = $this->buildMembre(['manifestation' => true]);
        $dossier = $this->buildDossier(['ID_GENRE' => 8]);

        self::assertTrue($this->checker->isDossierConcerneMembre($membre, $dossier));
    }

    public function testRetourneFauxSiGenreManifestationMaisMembreSansFlag(): void
    {
        $membre = $this->buildMembre(['manifestation' => false]);
        $dossier = $this->buildDossier(['ID_GENRE' => 8]);

        self::assertFalse($this->checker->isDossierConcerneMembre($membre, $dossier));
    }

    public function testRetourneVraiSiCategorieEtTypeActiviteCorrespondent(): void
    {
        $membre = $this->buildMembre([
            'categories' => [5 => ['ID_COMMISSIONMEMBRE' => 1]],
            'types' => [10 => ['ID_COMMISSIONMEMBRE' => 1]],
        ]);
        $dossier = $this->buildDossier(['ID_CATEGORIE' => 5, 'ID_TYPEACTIVITE' => 10]);

        self::assertTrue($this->checker->isDossierConcerneMembre($membre, $dossier));
    }

    public function testRetourneFauxSiCategorieSansTypeActivite(): void
    {
        $membre = $this->buildMembre([
            'categories' => [5 => ['ID_COMMISSIONMEMBRE' => 1]],
            'types' => [],
        ]);
        $dossier = $this->buildDossier(['ID_CATEGORIE' => 5, 'ID_TYPEACTIVITE' => 10]);

        self::assertFalse($this->checker->isDossierConcerneMembre($membre, $dossier));
    }

    public function testRetourneVraiSiClasseCorrespond(): void
    {
        $membre = $this->buildMembre([
            'classes' => [3 => ['ID_COMMISSIONMEMBRE' => 1]],
        ]);
        $dossier = $this->buildDossier(['ID_CLASSE' => 3]);

        self::assertTrue($this->checker->isDossierConcerneMembre($membre, $dossier));
    }

    public function testRetourneFauxSiClasseNeCorrespondPas(): void
    {
        $membre = $this->buildMembre([
            'classes' => [3 => ['ID_COMMISSIONMEMBRE' => 1]],
        ]);
        $dossier = $this->buildDossier(['ID_CLASSE' => 4]);

        self::assertFalse($this->checker->isDossierConcerneMembre($membre, $dossier));
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function buildMembre(array $overrides = []): array
    {
        return array_merge([
            'manifestation' => false,
            'categories' => [],
            'types' => [],
            'classes' => [],
            'dossiertypes' => [1 => ['ID_COMMISSIONMEMBRE' => 1]],
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function buildDossier(array $overrides = []): array
    {
        $infos = array_merge([
            'ID_GENRE' => 1,
            'ID_CATEGORIE' => 1,
            'ID_TYPEACTIVITE' => 1,
            'ID_CLASSE' => 1,
        ], $overrides);

        return [
            'TYPE_DOSSIER' => $overrides['TYPE_DOSSIER'] ?? 1,
            'infosEtab' => [
                'informations' => $infos,
            ],
        ];
    }
}
