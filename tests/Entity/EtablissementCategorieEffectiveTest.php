<?php

namespace App\Tests\Entity;

use App\Constant\GenreEtablissement;
use App\Entity\Etablissement;
use App\Entity\EtablissementCategorie;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Component\Asset\Packages;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class EtablissementCategorieEffectiveTest extends TestCase
{
    /**
     * @dataProvider categorieProvider
     */
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
        self::assertSame($attendue, null !== $categorie ? $categorie->getId() : null);
        self::assertSame($categoriePropre, null !== $etablissement->getLatestInformations()->getCategorie() ? $etablissement->getLatestInformations()->getCategorie()->getId() : null);
    }

    public function categorieProvider(): array
    {
        return [
            'cellule sans categorie propre' => [GenreEtablissement::CELLULE, 'actif', null, 5, 5],
            'categorie propre differente' => [GenreEtablissement::CELLULE, 'actif', 2, 5, 5],
            'parent sans categorie' => [GenreEtablissement::CELLULE, 'actif', 2, null, null],
            'parent sans fiche' => [GenreEtablissement::CELLULE, 'sans-fiche', 2, null, null],
            'parent supprime' => [GenreEtablissement::CELLULE, 'supprime', 2, 5, 2],
            'cellule sans parent' => [GenreEtablissement::CELLULE, 'absent', 2, null, 2],
            'ERP inchange' => [GenreEtablissement::ERP, 'actif', 2, 5, 2],
            'meme categorie' => [GenreEtablissement::CELLULE, 'actif', 5, 5, 5],
        ];
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

        self::assertSame(5, $cellule->getCategorieEffective()->getId());
    }

    /**
     * @dataProvider ficheProvider
     */
    public function testFicheAfficheCategorieEtJustifiantClassement(?int $categorieParentId, bool $afficheCategorie, bool $afficheJustifiant): void
    {
        $cellule = new Etablissement();
        $parent = new Etablissement();
        $this->addInformations($cellule, 1, GenreEtablissement::CELLULE, 2);
        $this->addInformations($parent, 1, GenreEtablissement::ERP, $categorieParentId);
        $cellule->addParent($parent);
        $categorie = $parent->getLatestInformations()->getCategorie();
        if (null !== $categorie) {
            $categorie->method('getLibelle')->willReturn($categorieParentId.'ème catégorie');
        }

        $twig = new Environment(new FilesystemLoader(dirname(__DIR__, 2).'/templates'), ['strict_variables' => true]);
        $twig->addExtension(new AssetExtension(new Packages()));
        $twig->addFunction(new TwigFunction('asset_exist', static function (): bool { return false; }));
        $html = $twig->render('etablissement/_informations/_general.html.twig', [
            'etablissement' => $cellule,
            'infos' => $cellule->getLatestInformations(),
            'periodicity' => null,
            'default_periodicite' => null,
            'fils_ordonnes' => [],
        ]);

        self::assertSame($afficheCategorie, false !== strpos($html, '<dt>Catégorie</dt>'));
        self::assertSame($afficheJustifiant, false !== strpos($html, '<dt>Justifiant le classement</dt>'));
        if ($afficheCategorie) {
            self::assertContains($categorieParentId.'ème catégorie', $html);
        }
    }

    public function ficheProvider(): array
    {
        return [
            'parent en cinquieme categorie' => [5, true, true],
            'parent en deuxieme categorie' => [2, true, false],
            'parent sans categorie' => [null, false, false],
        ];
    }

    private function addInformations(Etablissement $etablissement, int $id, int $genreId, ?int $categorieId): void
    {
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => $genreId]);
        $categorie = null !== $categorieId ? $this->createConfiguredMock(EtablissementCategorie::class, ['getId' => $categorieId]) : null;
        $informations = new EtablissementInformations();
        $informations->setGenre($genre);
        $informations->setCategorie($categorie);
        $idProperty = new \ReflectionProperty(EtablissementInformations::class, 'id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($informations, $id);
        $etablissement->addInformations($informations);
    }
}
