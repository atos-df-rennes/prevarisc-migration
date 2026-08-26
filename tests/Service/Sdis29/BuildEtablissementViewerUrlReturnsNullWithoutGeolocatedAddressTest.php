<?php

namespace App\Tests\Service\Sdis29;

use App\Entity\Etablissement;
use App\Entity\EtablissementAdresse;
use App\Service\Sdis29\GeobretagneUrlBuilder;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class BuildEtablissementViewerUrlReturnsNullWithoutGeolocatedAddressTest extends TestCase
{
    public function testBuildEtablissementViewerUrlReturnsNullWithoutGeolocatedAddress(): void
    {
        $builder = new GeobretagneUrlBuilder();

        $adresse = $this->createMock(EtablissementAdresse::class);
        $adresse->method('getLongitude')->willReturn(null);
        $adresse->method('getLatitude')->willReturn(null);

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection([$adresse]));

        $result = $builder->buildEtablissementViewerUrl(
            'https://sviewer.example/index.html',
            $etablissement,
            [],
            'https://app.example/sdis29/etablissement/1/kml'
        );

        $this->assertNull($result);
    }
}
