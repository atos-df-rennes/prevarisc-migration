<?php

namespace App\Tests\Service\Sdis29;

use App\Entity\Etablissement;
use App\Entity\EtablissementAdresse;
use App\Entity\EtablissementInformations;
use App\Service\Sdis29\GeobretagneUrlBuilder;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class BuildEtablissementViewerUrlBuildsExpectedUrlTest extends TestCase
{
    public function testBuildEtablissementViewerUrlBuildsExpectedUrl(): void
    {
        $builder = new GeobretagneUrlBuilder();

        $adresse = $this->createMock(EtablissementAdresse::class);
        $adresse->method('getLongitude')->willReturn(-4.0);
        $adresse->method('getLatitude')->willReturn(48.0);

        $informations = $this->createMock(EtablissementInformations::class);
        $informations->method('getLibelle')->willReturn('Caserne de test');

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection([$adresse]));
        $etablissement->method('getLatestInformationsOrNull')->willReturn($informations);

        $result = $builder->buildEtablissementViewerUrl(
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
}
