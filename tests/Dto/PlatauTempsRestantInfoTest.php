<?php

namespace App\Tests\Dto;

use App\Dto\PlatauTempsRestantInfo;
use PHPUnit\Framework\TestCase;

class PlatauTempsRestantInfoTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $info = new PlatauTempsRestantInfo('5 jours', 'warning');

        $this->assertSame('5 jours', $info->getTempsRestant());
        $this->assertSame('warning', $info->getCouleur());
        $this->assertTrue($info->hasInfo());
    }

    public function testEmptyFactory(): void
    {
        $info = PlatauTempsRestantInfo::empty();

        $this->assertNull($info->getTempsRestant());
        $this->assertNull($info->getCouleur());
        $this->assertFalse($info->hasInfo());
    }

    public function testHasInfoReturnsFalseWhenTempsRestantIsNull(): void
    {
        $info = new PlatauTempsRestantInfo(null, 'warning');

        $this->assertFalse($info->hasInfo());
    }

    public function testHasInfoReturnsTrueWhenTempsRestantIsPresent(): void
    {
        $info = new PlatauTempsRestantInfo('2 mois', null);

        $this->assertTrue($info->hasInfo());
    }
}
