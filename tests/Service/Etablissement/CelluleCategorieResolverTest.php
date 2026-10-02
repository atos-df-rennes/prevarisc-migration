<?php

declare(strict_types=1);

namespace App\Tests\Service\Etablissement;

use App\Constant\GenreEtablissement;
use App\Service\Etablissement\CelluleCategorieResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CelluleCategorieResolverTest extends TestCase
{
    #[DataProvider('categorieProvider')]
    public function testResoutUniquementLaCategorie(array $informations, ?array $parent, array $categorieAttendue): void
    {
        $informations['LIBELLE_ETABLISSEMENTINFORMATIONS'] = 'Cellule Type M';
        $informations['ID_TYPEACTIVITE'] = 10;
        $informations['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] = 42;

        $this->assertSame(array_replace($informations, $categorieAttendue), CelluleCategorieResolver::resolve($informations, $parent));
    }

    public static function categorieProvider(): \Iterator
    {
        $cellule = ['ID_GENRE' => '3', 'ID_CATEGORIE' => '2', 'LIBELLE_CATEGORIE' => '2ème catégorie'];
        $parent = ['ID_CATEGORIE' => '5', 'LIBELLE_CATEGORIE' => '5ème catégorie'];
        $vide = ['ID_CATEGORIE' => null, 'LIBELLE_CATEGORIE' => null];
        yield 'parent categorise' => [$cellule, $parent, $parent];
        yield 'cellule sans categorie' => [array_replace($cellule, $vide), $parent, $parent];
        yield 'parent sans categorie' => [$cellule, $vide, $vide];
        yield 'parent sans fiche' => [$cellule, [], $vide];
        yield 'sans parent actif' => [$cellule, null, []];
        yield 'ERP inchange' => [array_replace($cellule, ['ID_GENRE' => GenreEtablissement::ERP]), $parent, []];
        yield 'meme categorie' => [array_replace($cellule, $parent), $parent, $parent];
        yield 'genre entier' => [array_replace($cellule, ['ID_GENRE' => GenreEtablissement::CELLULE]), $parent, $parent];
    }
}
