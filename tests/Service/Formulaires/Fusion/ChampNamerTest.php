<?php

namespace App\Tests\Service\Formulaires\Fusion;

use App\Service\Formulaires\Fusion\ChampNamer;
use PHPUnit\Framework\TestCase;

class ChampNamerTest extends TestCase
{
    /**
     * @dataProvider fusionNameProvider
     */
    public function testGetDescriptifFieldFusionName(string $baseObject, array $options, string $expected): void
    {
        $this->assertSame($expected, ChampNamer::getFullFusionName($baseObject, $options));
    }

    public static function fusionNameProvider(): array
    {
        return [
            'simple field with rubrique' => ['descriptifEtablissement', ['Ma Rubrique', 'Mon Champ'], 'descriptifEtablissement-ma_rubrique-mon_champ'],
            'formatted field without rubrique' => ['descriptifEtablissement', ['Mon Champ d\'enfer "Personnalisé"'], 'descriptifEtablissement-mon_champ_d_enfer__personnalisé_'],
            'field with leading and trailing spaces' => ['descriptifEtablissement', ['  Mon champ avec des espaces '], 'descriptifEtablissement-mon_champ_avec_des_espaces'],
        ];
    }

    /**
     * @dataProvider loopNameProvider
     */
    public function testGetDescriptifLoopName(string $initialName, string $expected): void
    {
        $this->assertSame($expected, ChampNamer::getLoopName($initialName));
    }

    public static function loopNameProvider(): array
    {
        return [
            'simple field' => ['descriptif Rubrique Un champ', 'DescriptifRubriqueUnChamp'],
            'formatted field' => ['descriptif rUbrique un Champ d\'enfer "Personnalisé"', 'DescriptifRubriqueUnChampDEnferPersonnalisé'],
            'field with leading and trailing spaces' => ['descriptif Rubrique   Un champ avec des espaces ', 'DescriptifRubriqueUnChampAvecDesEspaces'],
        ];
    }
}
