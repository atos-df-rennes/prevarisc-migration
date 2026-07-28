<?php

namespace App\Tests\Service\Commission;

use App\Entity\Commission;
use App\Entity\CommissionRegle;
use App\Entity\CommissionRegleLocalSommeil;
use App\Entity\Commune;
use App\Entity\EtablissementCategorie;
use App\Entity\EtablissementType;
use App\Entity\Groupement;
use App\Entity\IghClasse;
use App\Service\Commission\CommissionRegleCompetenceService;
use PHPUnit\Framework\TestCase;

/**
 * Teste la logique d'appariement des règles de compétence d'une commission
 * avec les établissements ERP et IGH.
 */
final class CommissionRegleCompetenceServiceTest extends TestCase
{
    private CommissionRegleCompetenceService $service;

    protected function setUp(): void
    {
        $this->service = new CommissionRegleCompetenceService();
    }

    public function testRetourneTableauVideSiAucuneRegle(): void
    {
        $commission = new Commission();
        $ets = [$this->buildErp('1', '75001', true, 1, 2)];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([], $result);
    }

    public function testErpMatchantParCommuneDirecteEstRetourne(): void
    {
        $regle = $this->buildRegleErp('75001', [1], [2], [1]);
        $commission = $this->buildCommissionAvecRegle($regle);
        $ets = [$this->buildErp('42', '75001', true, 1, 2)];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([42], $result);
    }

    public function testErpNonMatchantCommuneDifferente(): void
    {
        $regle = $this->buildRegleErp('75001', [1], [2], [1]);
        $commission = $this->buildCommissionAvecRegle($regle);
        $ets = [$this->buildErp('42', '75002', true, 1, 2)];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([], $result);
    }

    public function testErpNonMatchantTypeAbsentDeLaRegle(): void
    {
        $regle = $this->buildRegleErp('75001', [1], [2], [1]);
        $commission = $this->buildCommissionAvecRegle($regle);
        $ets = [$this->buildErp('42', '75001', true, 99, 2)];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([], $result);
    }

    public function testErpNonMatchantCategorieAbsenteDeLaRegle(): void
    {
        $regle = $this->buildRegleErp('75001', [1], [2], [1]);
        $commission = $this->buildCommissionAvecRegle($regle);
        $ets = [$this->buildErp('42', '75001', true, 1, 99)];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([], $result);
    }

    public function testErpNonMatchantLocalSommeilAbsentDeLaRegle(): void
    {
        $regle = $this->buildRegleErp('75001', [1], [2], [1]);
        $commission = $this->buildCommissionAvecRegle($regle);
        // localSommeil=false alors que la règle n'accepte que true (valeur 1)
        $ets = [$this->buildErp('42', '75001', false, 1, 2)];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([], $result);
    }

    public function testErpAvecCollectionTypesVideNaCorrespond(): void
    {
        // Une règle sans types = aucun ERP ne correspond (sémantique stricte)
        $regle = $this->buildRegleErp('75001', [], [2], [1]);
        $commission = $this->buildCommissionAvecRegle($regle);
        $ets = [$this->buildErp('42', '75001', true, 1, 2)];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([], $result);
    }

    public function testErpAvecLocalSommeilFalseMatcheRegleAvecValeur0(): void
    {
        $regle = $this->buildRegleErp('75001', [1], [2], [0]);
        $commission = $this->buildCommissionAvecRegle($regle);
        $ets = [$this->buildErp('42', '75001', false, 1, 2)];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([42], $result);
    }

    public function testIghMatchantParCommuneEtClasseEstRetourne(): void
    {
        $regle = $this->buildRegleIgh('75001', [10]);
        $commission = $this->buildCommissionAvecRegle($regle);
        $ets = [$this->buildIgh('7', '75001', 10)];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([7], $result);
    }

    public function testIghNonMatchantClasseAbsenteDeLaRegle(): void
    {
        $regle = $this->buildRegleIgh('75001', [10]);
        $commission = $this->buildCommissionAvecRegle($regle);
        $ets = [$this->buildIgh('7', '75001', 20)];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([], $result);
    }

    public function testIghNonMatchantCommuneDifferente(): void
    {
        $regle = $this->buildRegleIgh('75001', [10]);
        $commission = $this->buildCommissionAvecRegle($regle);
        $ets = [$this->buildIgh('7', '75002', 10)];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([], $result);
    }

    public function testMatchViaGroupementEstRetourne(): void
    {
        $commune = $this->buildCommune('75001');
        $groupement = new Groupement();
        $groupement->addCommune($commune);

        $regle = new CommissionRegle();
        $regle->setGroupement($groupement);
        $regle->addType($this->buildType(1));
        $regle->addCategory($this->buildCategorie(2));
        $regle->addLocalSommeil($this->buildLocalSommeil(1));

        $commission = $this->buildCommissionAvecRegle($regle);
        $ets = [$this->buildErp('42', '75001', true, 1, 2)];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([42], $result);
    }

    public function testNonMatchGroupementNePossedantPasLaCommune(): void
    {
        $commune = $this->buildCommune('75002');
        $groupement = new Groupement();
        $groupement->addCommune($commune);

        $regle = new CommissionRegle();
        $regle->setGroupement($groupement);
        $regle->addType($this->buildType(1));
        $regle->addCategory($this->buildCategorie(2));
        $regle->addLocalSommeil($this->buildLocalSommeil(1));

        $commission = $this->buildCommissionAvecRegle($regle);
        $ets = [$this->buildErp('42', '75001', true, 1, 2)];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([], $result);
    }

    public function testNonMatchSansGroupementNiCommune(): void
    {
        // Règle sans commune ni groupement → communeMatches retourne false
        $regle = new CommissionRegle();
        $regle->addType($this->buildType(1));
        $regle->addCategory($this->buildCategorie(2));
        $regle->addLocalSommeil($this->buildLocalSommeil(1));

        $commission = $this->buildCommissionAvecRegle($regle);
        $ets = [$this->buildErp('42', '75001', true, 1, 2)];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([], $result);
    }

    public function testGenreNonSupporteNonRetourne(): void
    {
        $regle = $this->buildRegleErp('75001', [1], [2], [1]);
        $commission = $this->buildCommissionAvecRegle($regle);
        // Genre 3 = ni ERP (2) ni IGH (5)
        $ets = [[
            'ID_ETABLISSEMENTINFORMATIONS' => '42',
            'ID_GENRE' => '3',
            'NUMINSEE_COMMUNE' => '75001',
            'LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS' => true,
            'ID_TYPE' => '1',
            'ID_CATEGORIE' => '2',
            'ID_CLASSE' => null,
        ]];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([], $result);
    }

    public function testEtablissementRetournuUneSeuleFoisAvecPlusieursReglesCompatibles(): void
    {
        $regle1 = $this->buildRegleErp('75001', [1], [2], [1]);
        $regle2 = $this->buildRegleErp('75001', [1], [2], [1]);

        $commission = new Commission();
        $commission->addRegle($regle1);
        $commission->addRegle($regle2);

        $ets = [$this->buildErp('42', '75001', true, 1, 2)];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([42], $result);
        self::assertCount(1, $result);
    }

    public function testMultiplesEtablissementsMatchantsSontTousRetournes(): void
    {
        $regle = $this->buildRegleErp('75001', [1], [2], [1]);
        $commission = $this->buildCommissionAvecRegle($regle);
        $ets = [
            $this->buildErp('10', '75001', true, 1, 2),
            $this->buildErp('11', '75001', true, 1, 2),
        ];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([10, 11], $result);
    }

    public function testErpNonMatchantNestPasRetourne(): void
    {
        $regle = $this->buildRegleErp('75001', [1], [2], [1]);
        $commission = $this->buildCommissionAvecRegle($regle);
        $ets = [
            $this->buildErp('10', '75001', true, 1, 2),  // match
            $this->buildErp('11', '75002', true, 1, 2),  // mauvaise commune
        ];

        $result = $this->service->findMatchingEtablissementIds($commission, $ets);

        self::assertSame([10], $result);
    }

    // -------------------------------------------------------------------------
    // Builders
    // -------------------------------------------------------------------------

    /**
     * @param int[] $typeIds
     * @param int[] $categorieIds
     * @param int[] $localSommeilValues
     */
    private function buildRegleErp(string $numInsee, array $typeIds, array $categorieIds, array $localSommeilValues): CommissionRegle
    {
        $regle = new CommissionRegle();
        $regle->setCommune($this->buildCommune($numInsee));

        foreach ($typeIds as $id) {
            $regle->addType($this->buildType($id));
        }

        foreach ($categorieIds as $id) {
            $regle->addCategory($this->buildCategorie($id));
        }

        foreach ($localSommeilValues as $val) {
            $regle->addLocalSommeil($this->buildLocalSommeil($val));
        }

        return $regle;
    }

    /**
     * @param int[] $classeIds
     */
    private function buildRegleIgh(string $numInsee, array $classeIds): CommissionRegle
    {
        $regle = new CommissionRegle();
        $regle->setCommune($this->buildCommune($numInsee));

        foreach ($classeIds as $id) {
            $regle->addClass($this->buildClasse($id));
        }

        return $regle;
    }

    private function buildCommissionAvecRegle(CommissionRegle $regle): Commission
    {
        $commission = new Commission();
        $commission->addRegle($regle);

        return $commission;
    }

    /**
     * @return array{ID_ETABLISSEMENTINFORMATIONS: string, ID_GENRE: string, NUMINSEE_COMMUNE: string, LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS: bool|null, ID_TYPE: string|null, ID_CATEGORIE: string|null, ID_CLASSE: string|null}
     */
    private function buildErp(string $id, string $numInsee, ?bool $localSommeil, int $typeId, int $categorieId): array
    {
        return [
            'ID_ETABLISSEMENTINFORMATIONS' => $id,
            'ID_GENRE' => '2',
            'NUMINSEE_COMMUNE' => $numInsee,
            'LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS' => $localSommeil,
            'ID_TYPE' => (string) $typeId,
            'ID_CATEGORIE' => (string) $categorieId,
            'ID_CLASSE' => null,
        ];
    }

    /**
     * @return array{ID_ETABLISSEMENTINFORMATIONS: string, ID_GENRE: string, NUMINSEE_COMMUNE: string, LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS: bool|null, ID_TYPE: string|null, ID_CATEGORIE: string|null, ID_CLASSE: string|null}
     */
    private function buildIgh(string $id, string $numInsee, int $classeId): array
    {
        return [
            'ID_ETABLISSEMENTINFORMATIONS' => $id,
            'ID_GENRE' => '5',
            'NUMINSEE_COMMUNE' => $numInsee,
            'LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS' => null,
            'ID_TYPE' => null,
            'ID_CATEGORIE' => null,
            'ID_CLASSE' => (string) $classeId,
        ];
    }

    private function buildCommune(string $numInsee): Commune
    {
        $commune = new Commune();
        $prop = new \ReflectionProperty(Commune::class, 'id');
        $prop->setValue($commune, $numInsee);

        return $commune;
    }

    private function buildType(int $id): EtablissementType
    {
        $type = new EtablissementType();
        $prop = new \ReflectionProperty(EtablissementType::class, 'id');
        $prop->setValue($type, $id);

        return $type;
    }

    private function buildCategorie(int $id): EtablissementCategorie
    {
        $categorie = new EtablissementCategorie();
        $prop = new \ReflectionProperty(EtablissementCategorie::class, 'id');
        $prop->setValue($categorie, $id);

        return $categorie;
    }

    private function buildClasse(int $id): IghClasse
    {
        $classe = new IghClasse();
        $prop = new \ReflectionProperty(IghClasse::class, 'id');
        $prop->setValue($classe, $id);

        return $classe;
    }

    private function buildLocalSommeil(int $value): CommissionRegleLocalSommeil
    {
        $ls = new CommissionRegleLocalSommeil();
        $ls->setLocalSommeil($value);

        return $ls;
    }
}
