<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Twig\PlatauHealthcheckExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PlatauHealthcheckExtensionTest extends TestCase
{
    /**
     * @return iterable<string, array{0: bool, 1: bool, 2: bool}>
     */
    public static function configurations(): iterable
    {
        yield 'actif' => [false, false, true];
        yield 'plat\'au désactivé' => [true, false, false];
        yield 'healthcheck désactivé' => [false, true, false];
    }

    #[DataProvider('configurations')]
    public function testIsEnabled(bool $deactivatePlatau, bool $noHealthcheck, bool $expected): void
    {
        $this->assertSame($expected, new PlatauHealthcheckExtension($deactivatePlatau, $noHealthcheck)->isEnabled());
    }
}
