<?php

declare(strict_types=1);

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
        $adresse = $this->createStub(EtablissementAdresse::class);
        $adresse->method('getLongitude')->willReturn(null);
        $adresse->method('getLatitude')->willReturn(null);

        $etablissement = $this->createStub(Etablissement::class);
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection([$adresse]));

        $result = $this->builder->buildEtablissementViewerUrl(
            'https://sviewer.example/index.html',
            $etablissement,
            [],
            'https://app.example/sdis29/etablissement/1/kml'
        );

        $this->assertNull($result);
    }

    public function testBuildEtablissementViewerUrlBuildsExpectedUrl(): void
    {
        $adresse = $this->createStub(EtablissementAdresse::class);
        $adresse->method('getLongitude')->willReturn(-4.0);
        $adresse->method('getLatitude')->willReturn(48.0);

        $informations = $this->createStub(EtablissementInformations::class);
        $informations->method('getLibelle')->willReturn('Caserne de test');

        $etablissement = $this->createStub(Etablissement::class);
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection([$adresse]));
        $etablissement->method('getLatestInformationsOrNull')->willReturn($informations);

        $result = $this->builder->buildEtablissementViewerUrl(
            'https://sviewer.example/index.html',
            $etablissement,
            [],
            'https://app.example/sdis29/etablissement/1/kml'
        );

        $this->assertNotNull($result);
        $this->assertStringStartsWith('https://sviewer.example/index.html?', $result);
        $this->assertStringContainsString('title=Caserne+de+test', $result);
        $this->assertStringContainsString('kml=', $result);
    }

    public function testBuildAdminViewerUrlBuildsExpectedUrl(): void
    {
        $result = $this->builder->buildAdminViewerUrl('https://sviewer.example/index.html', []);

        $this->assertNotNull($result);
        $this->assertStringStartsWith('https://sviewer.example/index.html?', $result);
        $this->assertStringContainsString('title=Exemple+de+carte+g%C3%A9obretagne', $result);
        $this->assertStringNotContainsString('kml=', $result);
    }
}
