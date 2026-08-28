<?php

namespace App\Tests\Service\Sdis29;

use App\Entity\Etablissement;
use App\Entity\EtablissementAdresse;
use App\Entity\EtablissementInformations;
use App\Service\Sdis29\GeobretagneUrlBuilder;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * Teste la construction des URLs du visualiseur cartographique Géobretagne
 * (sviewer), pour la fiche établissement et pour la page d'administration.
 */
final class GeobretagneUrlBuilderTest extends TestCase
{
    private GeobretagneUrlBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new GeobretagneUrlBuilder();
    }

    public function testBuildEtablissementViewerUrlReturnsNullWithoutGeolocatedAddress(): void
    {
        $adresse = $this->createMock(EtablissementAdresse::class);
        $adresse->method('getLongitude')->willReturn(null);
        $adresse->method('getLatitude')->willReturn(null);

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection([$adresse]));

        $result = $this->builder->buildEtablissementViewerUrl(
            'https://sviewer.example/index.html',
            $etablissement,
            [],
            'https://app.example/sdis29/etablissement/1/kml'
        );

        self::assertNull($result);
    }

    public function testBuildEtablissementViewerUrlBuildsExpectedUrl(): void
    {
        $adresse = $this->createMock(EtablissementAdresse::class);
        $adresse->method('getLongitude')->willReturn(-4.0);
        $adresse->method('getLatitude')->willReturn(48.0);

        $informations = $this->createMock(EtablissementInformations::class);
        $informations->method('getLibelle')->willReturn('Caserne de test');

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection([$adresse]));
        $etablissement->method('getLatestInformationsOrNull')->willReturn($informations);

        $result = $this->builder->buildEtablissementViewerUrl(
            'https://sviewer.example/index.html',
            $etablissement,
            [],
            'https://app.example/sdis29/etablissement/1/kml'
        );

        self::assertNotNull($result);
        self::assertStringStartsWith('https://sviewer.example/index.html?', $result);
        self::assertStringContainsString('title=Caserne+de+test', $result);
        self::assertStringContainsString('kml=', $result);
    }

    public function testBuildAdminViewerUrlBuildsExpectedUrl(): void
    {
        $result = $this->builder->buildAdminViewerUrl('https://sviewer.example/index.html', []);

        self::assertNotNull($result);
        self::assertStringStartsWith('https://sviewer.example/index.html?', $result);
        self::assertStringContainsString('title=Exemple+de+carte+g%C3%A9obretagne', $result);
        self::assertStringNotContainsString('kml=', $result);
    }
}
