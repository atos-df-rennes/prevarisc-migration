<?php

namespace App\Tests\Service\Alerte;

use App\Constant\GenreEtablissement;
use App\Entity\Etablissement;
use App\Entity\EtablissementCategorie;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use App\Service\Alerte\AvisService;
use App\Service\Alerte\MessageService;
use PHPUnit\Framework\TestCase;

class MessageServiceTest extends TestCase
{
    /**
     * @dataProvider categorieProvider
     */
    public function testBaliseCategorieEffective(int $genreId, bool $avecParent, ?string $libelleParent, string $attendu): void
    {
        $etablissement = new Etablissement();
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => $genreId]);
        $informations = new EtablissementInformations();
        $informations->setGenre($genre);
        $informations->setLibelle('Cellule Type M');
        $informations->setCategorie((new EtablissementCategorie())->setLibelle('2ème catégorie'));

        $etablissement->addInformations($informations);

        if ($avecParent) {
            $parent = new Etablissement();
            $parentInformations = new EtablissementInformations();
            $parentInformations->setCategorie(null !== $libelleParent ? (new EtablissementCategorie())->setLibelle($libelleParent) : null);
            $parent->addInformations($parentInformations);
            $etablissement->addParent($parent);
        }

        $avisService = $this->createConfiguredMock(AvisService::class, ['getTexte' => 'Favorable']);
        $service = new MessageService($avisService);

        self::assertSame('Cellule Type M : '.$attendu.' / Favorable / {inconnue}', $service->generer('{etablissementLibelle} : {categorieEtablissement} / {etablissementAvis} / {inconnue}', $etablissement));
    }

    public function categorieProvider(): array
    {
        return [
            'cellule avec parent' => [GenreEtablissement::CELLULE, true, '5ème catégorie', '5ème catégorie'],
            'parent sans categorie' => [GenreEtablissement::CELLULE, true, null, ''],
            'cellule sans parent' => [GenreEtablissement::CELLULE, false, null, '2ème catégorie'],
            'ERP inchange' => [GenreEtablissement::ERP, true, '5ème catégorie', '2ème catégorie'],
        ];
    }
}
