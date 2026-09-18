<?php

declare(strict_types=1);

namespace App\Tests\Service\Formulaires\Fusion;

use App\Service\Formulaires\Fusion\ChampNamer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ChampNamerTest extends TestCase
{
    #[DataProvider('fusionNameProvider')]
    public function testGetDescriptifFieldFusionName(string $baseObject, array $options, string $expected): void
    {
        $this->assertSame($expected, ChampNamer::getFullFusionName($baseObject, $options));
    }

    public static function fusionNameProvider(): \Iterator
    {
        yield 'simple field with rubrique' => ['descriptifEtablissement', ['Ma Rubrique', 'Mon Champ'], 'descriptifEtablissement-ma_rubrique-mon_champ'];
        yield 'formatted field without rubrique' => ['descriptifEtablissement', ['Mon Champ d\'enfer "Personnalisé"'], 'descriptifEtablissement-mon_champ_d_enfer__personnalisé_'];
        yield 'field with leading and trailing spaces' => ['descriptifEtablissement', ['  Mon champ avec des espaces '], 'descriptifEtablissement-mon_champ_avec_des_espaces'];
    }

    #[DataProvider('loopNameProvider')]
    public function testGetDescriptifLoopName(string $initialName, string $expected): void
    {
        $this->assertSame($expected, ChampNamer::getLoopName($initialName));
    }

    public static function loopNameProvider(): \Iterator
    {
        yield 'simple field' => ['descriptif Rubrique Un champ', 'DescriptifRubriqueUnChamp'];
        yield 'formatted field' => ['descriptif rUbrique un Champ d\'enfer "Personnalisé"', 'DescriptifRubriqueUnChampDEnferPersonnalisé'];
        yield 'field with leading and trailing spaces' => ['descriptif Rubrique   Un champ avec des espaces ', 'DescriptifRubriqueUnChampAvecDesEspaces'];
    }
}
