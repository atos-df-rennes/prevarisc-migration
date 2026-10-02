<?php

namespace App\Tests\Service\Etablissement;

use App\Constant\GenreEtablissement;
use App\Service\Etablissement\CelluleCategorieResolver;
use PHPUnit\Framework\TestCase;

class CelluleCategorieResolverTest extends TestCase
{
    /**
     * @dataProvider categorieProvider
     */
    public function testResoutUniquementLaCategorie(array $informations, ?array $parent, array $categorieAttendue): void
    {
        $informations['LIBELLE_ETABLISSEMENTINFORMATIONS'] = 'Cellule Type M';
        $informations['ID_TYPEACTIVITE'] = 10;
        $informations['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] = 42;

        self::assertSame(array_replace($informations, $categorieAttendue), CelluleCategorieResolver::resolve($informations, $parent));
    }

    public function categorieProvider(): array
    {
        $cellule = ['ID_GENRE' => '3', 'ID_CATEGORIE' => '2', 'LIBELLE_CATEGORIE' => '2ème catégorie'];
        $parent = ['ID_CATEGORIE' => '5', 'LIBELLE_CATEGORIE' => '5ème catégorie'];
        $vide = ['ID_CATEGORIE' => null, 'LIBELLE_CATEGORIE' => null];

        return [
            'parent categorise' => [$cellule, $parent, $parent],
            'cellule sans categorie' => [array_replace($cellule, $vide), $parent, $parent],
            'parent sans categorie' => [$cellule, $vide, $vide],
            'parent sans fiche' => [$cellule, [], $vide],
            'sans parent actif' => [$cellule, null, []],
            'ERP inchange' => [array_replace($cellule, ['ID_GENRE' => GenreEtablissement::ERP]), $parent, []],
            'meme categorie' => [array_replace($cellule, $parent), $parent, $parent],
            'genre entier' => [array_replace($cellule, ['ID_GENRE' => GenreEtablissement::CELLULE]), $parent, $parent],
        ];
    }
}
