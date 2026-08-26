<?php

namespace App\Tests\Service\Sdis29;

use App\Service\Sdis29\GeobretagneUrlBuilder;
use PHPUnit\Framework\TestCase;

class BuildAdminViewerUrlBuildsExpectedUrlTest extends TestCase
{
    public function testBuildAdminViewerUrlBuildsExpectedUrl(): void
    {
        $builder = new GeobretagneUrlBuilder();

        $result = $builder->buildAdminViewerUrl('https://sviewer.example/index.html', []);

        $this->assertNotNull($result);
        $this->assertStringStartsWith('https://sviewer.example/index.html?', $result);
        $this->assertStringContainsString('title=Exemple+de+carte+g%C3%A9obretagne', $result);
        $this->assertStringNotContainsString('kml=', $result);
    }
}
