<?php

declare(strict_types=1);

namespace App\Tests\Service\Rapport;

use App\Constant\TypePrescription;
use App\Service\Rapport\PrescriptionArrayHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PrescriptionArrayHelperTest extends TestCase
{
    // =========================================================================
    // withoutLevees
    // =========================================================================

    public function testWithoutLeveesRetientPrescriptionsSansDateLevee(): void
    {
        $input = [
            0 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '']],
            1 => [0 => ['DATE_LEVEE' => '01/06/2024', 'ID_DOSSIER_REPRISE' => '']],
        ];

        $result = PrescriptionArrayHelper::withoutLevees($input);

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayNotHasKey(1, $result);
    }

    public function testWithoutLeveesRetourneVideSiToutesSontLevees(): void
    {
        $input = [
            0 => [0 => ['DATE_LEVEE' => '01/06/2024', 'ID_DOSSIER_REPRISE' => '']],
            1 => [0 => ['DATE_LEVEE' => '15/03/2023', 'ID_DOSSIER_REPRISE' => '']],
        ];

        $this->assertSame([], PrescriptionArrayHelper::withoutLevees($input));
    }

    public function testWithoutLeveesRetourneTableauInchangeSiAucuneLevee(): void
    {
        $input = [
            0 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '']],
            1 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '5']],
        ];

        $this->assertSame($input, PrescriptionArrayHelper::withoutLevees($input));
    }

    public function testWithoutLeveesAvecTableauVide(): void
    {
        $this->assertSame([], PrescriptionArrayHelper::withoutLevees([]));
    }

    // =========================================================================
    // withoutActuals
    // =========================================================================

    public function testWithoutActualsGardeSeulementLesReprises(): void
    {
        $input = [
            0 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '7']],
            1 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '']],
        ];

        $result = PrescriptionArrayHelper::withoutActuals($input);

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayNotHasKey(1, $result);
    }

    public function testWithoutActualsRetourneVideSiToutesActuelles(): void
    {
        $input = [
            0 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '']],
            1 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '']],
        ];

        $this->assertSame([], PrescriptionArrayHelper::withoutActuals($input));
    }

    public function testWithoutActualsRetourneTableauInchangeSiToutesReprises(): void
    {
        $input = [
            0 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '3']],
            1 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '9']],
        ];

        $this->assertSame($input, PrescriptionArrayHelper::withoutActuals($input));
    }

    public function testWithoutActualsAvecTableauVide(): void
    {
        $this->assertSame([], PrescriptionArrayHelper::withoutActuals([]));
    }

    // =========================================================================
    // withoutPrevious
    // =========================================================================

    public function testWithoutPreviousGardeSeulementLesActuelles(): void
    {
        $input = [
            0 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '']],
            1 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '3']],
        ];

        $result = PrescriptionArrayHelper::withoutPrevious($input);

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayNotHasKey(1, $result);
    }

    public function testWithoutPreviousRetourneVideSiToutesReprises(): void
    {
        $input = [
            0 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '5']],
            1 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '2']],
        ];

        $this->assertSame([], PrescriptionArrayHelper::withoutPrevious($input));
    }

    public function testWithoutPreviousRetourneTableauInchangeSiAucuneReprise(): void
    {
        $input = [
            0 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '']],
            1 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '']],
        ];

        $this->assertSame($input, PrescriptionArrayHelper::withoutPrevious($input));
    }

    public function testWithoutPreviousAvecTableauVide(): void
    {
        $this->assertSame([], PrescriptionArrayHelper::withoutPrevious([]));
    }

    // =========================================================================
    // buildTextearticle
    // =========================================================================

    public function testBuildTextearticleAvecTableauVide(): void
    {
        $this->assertSame('', PrescriptionArrayHelper::buildTextearticle([]));
    }

    public function testBuildTextearticleAvecUnePrescription(): void
    {
        $presc = [['LIBELLE_TEXTE' => 'R123', 'LIBELLE_ARTICLE' => 'Art.1']];

        $this->assertSame('R123 - Art.1 ', PrescriptionArrayHelper::buildTextearticle($presc));
    }

    public function testBuildTextearticleAvecDeuxPrescriptionsUtiliseVirgule(): void
    {
        $presc = [
            ['LIBELLE_TEXTE' => 'R123', 'LIBELLE_ARTICLE' => 'Art.1'],
            ['LIBELLE_TEXTE' => 'R456', 'LIBELLE_ARTICLE' => 'Art.2'],
        ];

        $this->assertSame('R123 - Art.1, R456 - Art.2 ', PrescriptionArrayHelper::buildTextearticle($presc));
    }

    public function testBuildTextearticleAvecTexteVideUtiliseSeparateurSeulement(): void
    {
        $presc = [['LIBELLE_TEXTE' => '', 'LIBELLE_ARTICLE' => 'Art.1']];

        $this->assertSame(' - Art.1 ', PrescriptionArrayHelper::buildTextearticle($presc));
    }

    public function testBuildTextearticleAvecArticleVideLaisseEspaceApres(): void
    {
        $presc = [['LIBELLE_TEXTE' => 'R123', 'LIBELLE_ARTICLE' => '']];

        $this->assertSame('R123 -  ', PrescriptionArrayHelper::buildTextearticle($presc));
    }

    // =========================================================================
    // getPrescriptionSegmentName
    // =========================================================================
    #[DataProvider('segmentNameProvider')]
    public function testGetPrescriptionSegmentName(int $type, string $contenu, string $expected): void
    {
        $this->assertSame($expected, PrescriptionArrayHelper::getPrescriptionSegmentName($type, $contenu));
    }

    /**
     * @return \Iterator<string, array<mixed>>
     */
    public static function segmentNameProvider(): \Iterator
    {
        yield 'reglementaire all' => [TypePrescription::REGLEMENTAIRE, 'all',       'listeReg'];
        yield 'reglementaire reprises' => [TypePrescription::REGLEMENTAIRE, 'reprises',  'listeRegReprises'];
        yield 'reglementaire actuelles' => [TypePrescription::REGLEMENTAIRE, 'actuelles', 'listeRegActuelles'];
        yield 'exploitation all' => [TypePrescription::EXPLOITATION,  'all',       'listeExploitation'];
        yield 'exploitation reprises' => [TypePrescription::EXPLOITATION,  'reprises',  'listeExploitationReprises'];
        yield 'exploitation actuelles' => [TypePrescription::EXPLOITATION,  'actuelles', 'listeExploitationActuelles'];
        yield 'amelioration all' => [TypePrescription::AMELIORATION,  'all',       'listeAmelioration'];
        yield 'amelioration reprises' => [TypePrescription::AMELIORATION,  'reprises',  'listeAmeliorationReprises'];
        yield 'amelioration actuelles' => [TypePrescription::AMELIORATION,  'actuelles', 'listeAmeliorationActuelles'];
        yield 'type inconnu' => [99,                              'all',       ''];
        yield 'contenu inconnu' => [TypePrescription::REGLEMENTAIRE, 'inconnu',   ''];
    }
}
