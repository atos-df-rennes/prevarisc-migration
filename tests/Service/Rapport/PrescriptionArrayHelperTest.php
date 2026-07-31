<?php

namespace App\Tests\Service\Rapport;

use App\Constant\TypePrescription;
use App\Service\Rapport\PrescriptionArrayHelper;
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

        self::assertArrayHasKey(0, $result);
        self::assertArrayNotHasKey(1, $result);
    }

    public function testWithoutLeveesRetourneVideSiToutesSontLevees(): void
    {
        $input = [
            0 => [0 => ['DATE_LEVEE' => '01/06/2024', 'ID_DOSSIER_REPRISE' => '']],
            1 => [0 => ['DATE_LEVEE' => '15/03/2023', 'ID_DOSSIER_REPRISE' => '']],
        ];

        self::assertSame([], PrescriptionArrayHelper::withoutLevees($input));
    }

    public function testWithoutLeveesRetourneTableauInchangeSiAucuneLevee(): void
    {
        $input = [
            0 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '']],
            1 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '5']],
        ];

        self::assertSame($input, PrescriptionArrayHelper::withoutLevees($input));
    }

    public function testWithoutLeveesAvecTableauVide(): void
    {
        self::assertSame([], PrescriptionArrayHelper::withoutLevees([]));
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

        self::assertArrayHasKey(0, $result);
        self::assertArrayNotHasKey(1, $result);
    }

    public function testWithoutActualsRetourneVideSiToutesActuelles(): void
    {
        $input = [
            0 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '']],
            1 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '']],
        ];

        self::assertSame([], PrescriptionArrayHelper::withoutActuals($input));
    }

    public function testWithoutActualsRetourneTableauInchangeSiToutesReprises(): void
    {
        $input = [
            0 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '3']],
            1 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '9']],
        ];

        self::assertSame($input, PrescriptionArrayHelper::withoutActuals($input));
    }

    public function testWithoutActualsAvecTableauVide(): void
    {
        self::assertSame([], PrescriptionArrayHelper::withoutActuals([]));
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

        self::assertArrayHasKey(0, $result);
        self::assertArrayNotHasKey(1, $result);
    }

    public function testWithoutPreviousRetourneVideSiToutesReprises(): void
    {
        $input = [
            0 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '5']],
            1 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '2']],
        ];

        self::assertSame([], PrescriptionArrayHelper::withoutPrevious($input));
    }

    public function testWithoutPreviousRetourneTableauInchangeSiAucuneReprise(): void
    {
        $input = [
            0 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '']],
            1 => [0 => ['DATE_LEVEE' => '', 'ID_DOSSIER_REPRISE' => '']],
        ];

        self::assertSame($input, PrescriptionArrayHelper::withoutPrevious($input));
    }

    public function testWithoutPreviousAvecTableauVide(): void
    {
        self::assertSame([], PrescriptionArrayHelper::withoutPrevious([]));
    }

    // =========================================================================
    // buildTextearticle
    // =========================================================================

    public function testBuildTextearticleAvecTableauVide(): void
    {
        self::assertSame('', PrescriptionArrayHelper::buildTextearticle([]));
    }

    public function testBuildTextearticleAvecUnePrescription(): void
    {
        $presc = [['LIBELLE_TEXTE' => 'R123', 'LIBELLE_ARTICLE' => 'Art.1']];

        self::assertSame('R123 - Art.1 ', PrescriptionArrayHelper::buildTextearticle($presc));
    }

    public function testBuildTextearticleAvecDeuxPrescriptionsUtiliseVirgule(): void
    {
        $presc = [
            ['LIBELLE_TEXTE' => 'R123', 'LIBELLE_ARTICLE' => 'Art.1'],
            ['LIBELLE_TEXTE' => 'R456', 'LIBELLE_ARTICLE' => 'Art.2'],
        ];

        self::assertSame('R123 - Art.1, R456 - Art.2 ', PrescriptionArrayHelper::buildTextearticle($presc));
    }

    public function testBuildTextearticleAvecTexteVideUtiliseSeparateurSeulement(): void
    {
        $presc = [['LIBELLE_TEXTE' => '', 'LIBELLE_ARTICLE' => 'Art.1']];

        self::assertSame(' - Art.1 ', PrescriptionArrayHelper::buildTextearticle($presc));
    }

    public function testBuildTextearticleAvecArticleVideLaisseEspaceApres(): void
    {
        $presc = [['LIBELLE_TEXTE' => 'R123', 'LIBELLE_ARTICLE' => '']];

        self::assertSame('R123 -  ', PrescriptionArrayHelper::buildTextearticle($presc));
    }

    // =========================================================================
    // getPrescriptionSegmentName
    // =========================================================================

    /**
     * @dataProvider segmentNameProvider
     */
    public function testGetPrescriptionSegmentName(int $type, string $contenu, string $expected): void
    {
        self::assertSame($expected, PrescriptionArrayHelper::getPrescriptionSegmentName($type, $contenu));
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function segmentNameProvider(): array
    {
        return [
            'reglementaire all' => [TypePrescription::REGLEMENTAIRE, 'all',       'listeReg'],
            'reglementaire reprises' => [TypePrescription::REGLEMENTAIRE, 'reprises',  'listeRegReprises'],
            'reglementaire actuelles' => [TypePrescription::REGLEMENTAIRE, 'actuelles', 'listeRegActuelles'],
            'exploitation all' => [TypePrescription::EXPLOITATION,  'all',       'listeExploitation'],
            'exploitation reprises' => [TypePrescription::EXPLOITATION,  'reprises',  'listeExploitationReprises'],
            'exploitation actuelles' => [TypePrescription::EXPLOITATION,  'actuelles', 'listeExploitationActuelles'],
            'amelioration all' => [TypePrescription::AMELIORATION,  'all',       'listeAmelioration'],
            'amelioration reprises' => [TypePrescription::AMELIORATION,  'reprises',  'listeAmeliorationReprises'],
            'amelioration actuelles' => [TypePrescription::AMELIORATION,  'actuelles', 'listeAmeliorationActuelles'],
            'type inconnu' => [99,                              'all',       ''],
            'contenu inconnu' => [TypePrescription::REGLEMENTAIRE, 'inconnu',   ''],
        ];
    }
}
