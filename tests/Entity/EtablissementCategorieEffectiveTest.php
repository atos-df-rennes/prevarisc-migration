<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Constant\GenreEtablissement;
use App\Entity\Etablissement;
use App\Entity\EtablissementCategorie;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EtablissementCategorieEffectiveTest extends TestCase
{
    #[DataProvider('categorieProvider')]
    public function testCategorieEffective(int $genreId, string $parentEtat, ?int $categoriePropre, ?int $categorieParent, ?int $attendue): void
    {
        $etablissement = new Etablissement();
        $this->addInformations($etablissement, 1, $genreId, $categoriePropre);

        if ('absent' !== $parentEtat) {
            $parent = new Etablissement();
            $etablissement->addParent($parent);
            if ('sans-fiche' !== $parentEtat) {
                $this->addInformations($parent, 1, GenreEtablissement::ERP, 1);
                $this->addInformations($parent, 2, GenreEtablissement::ERP, $categorieParent);
            }

            if ('supprime' === $parentEtat) {
                $parent->setDateSuppression(new \DateTime('2026-01-01'));
            }
        }

        $categorie = $etablissement->getCategorieEffective();
        $this->assertSame($attendue, $categorie?->getId());
        $this->assertSame($categoriePropre, $etablissement->getLatestInformations()->getCategorie() instanceof EtablissementCategorie ? $etablissement->getLatestInformations()->getCategorie()->getId() : null);
    }

    public static function categorieProvider(): \Iterator
    {
        yield 'cellule sans categorie propre' => [GenreEtablissement::CELLULE, 'actif', null, 5, 5];
        yield 'categorie propre differente' => [GenreEtablissement::CELLULE, 'actif', 2, 5, 5];
        yield 'parent sans categorie' => [GenreEtablissement::CELLULE, 'actif', 2, null, null];
        yield 'parent sans fiche' => [GenreEtablissement::CELLULE, 'sans-fiche', 2, null, null];
        yield 'parent supprime' => [GenreEtablissement::CELLULE, 'supprime', 2, 5, 2];
        yield 'cellule sans parent' => [GenreEtablissement::CELLULE, 'absent', 2, null, 2];
        yield 'ERP inchange' => [GenreEtablissement::ERP, 'actif', 2, 5, 2];
        yield 'meme categorie' => [GenreEtablissement::CELLULE, 'actif', 5, 5, 5];
    }

    public function testNeRemontePasAuGrandParent(): void
    {
        $cellule = new Etablissement();
        $parent = new Etablissement();
        $grandParent = new Etablissement();
        $this->addInformations($cellule, 1, GenreEtablissement::CELLULE, null);
        $this->addInformations($parent, 1, GenreEtablissement::CELLULE, 5);
        $this->addInformations($grandParent, 1, GenreEtablissement::ERP, 1);
        $cellule->addParent($parent);
        $parent->addParent($grandParent);

        $this->assertSame(5, $cellule->getCategorieEffective()->getId());
    }

    private function addInformations(Etablissement $etablissement, int $id, int $genreId, ?int $categorieId): void
    {
        $genre = $this->createConfiguredStub(Genre::class, ['getId' => $genreId]);
        $categorie = null !== $categorieId ? $this->createConfiguredStub(EtablissementCategorie::class, ['getId' => $categorieId]) : null;
        $informations = new EtablissementInformations();
        $informations->setGenre($genre);
        $informations->setCategorie($categorie);

        $idProperty = new \ReflectionProperty(EtablissementInformations::class, 'id');
        $idProperty->setValue($informations, $id);

        $etablissement->addInformations($informations);
    }
}
