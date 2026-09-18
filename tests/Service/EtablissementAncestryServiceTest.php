<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Etablissement;
use App\Service\EtablissementAncestryService;
use PHPUnit\Framework\TestCase;

final class EtablissementAncestryServiceTest extends TestCase
{
    public function testReturnsEmptyArrayWhenNoParent(): void
    {
        $service = new EtablissementAncestryService();
        $etablissement = new Etablissement();

        $this->assertSame([], $service->getAncetres($etablissement));
    }

    public function testReturnsDirectParentOnly(): void
    {
        $service = new EtablissementAncestryService();
        $parent = new Etablissement();
        $enfant = new Etablissement();
        $enfant->addParent($parent);

        $this->assertSame([$parent], $service->getAncetres($enfant));
    }

    public function testReturnsFullChainRootFirst(): void
    {
        $service = new EtablissementAncestryService();
        $racine = new Etablissement();
        $intermediaire = new Etablissement();
        $enfant = new Etablissement();

        $intermediaire->addParent($racine);
        $enfant->addParent($intermediaire);

        $this->assertSame([$racine, $intermediaire], $service->getAncetres($enfant));
    }
}
