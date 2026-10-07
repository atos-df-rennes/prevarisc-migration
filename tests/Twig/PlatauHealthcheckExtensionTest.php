<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Dto\Platau\HealthcheckResult;
use App\Dto\Platau\HealthcheckStatus;
use App\Dto\Platau\VersionInfo;
use App\Service\PlatauHealthcheckStatusProvider;
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
        $this->assertSame($expected, $this->buildExtension($deactivatePlatau, $noHealthcheck, null)->isEnabled());
    }

    #[DataProvider('configurations')]
    public function testCachedStatusIsOnlyReturnedWhenEnabled(bool $deactivatePlatau, bool $noHealthcheck, bool $enabled): void
    {
        $now = new \DateTimeImmutable();
        $status = new HealthcheckStatus(HealthcheckResult::success(new VersionInfo('1.0', null, null), null), $now, $now->modify('+300 seconds'));

        $this->assertSame($enabled ? $status : null, $this->buildExtension($deactivatePlatau, $noHealthcheck, $status)->getCachedStatus());
    }

    private function buildExtension(bool $deactivatePlatau, bool $noHealthcheck, ?HealthcheckStatus $cachedStatus): PlatauHealthcheckExtension
    {
        $provider = $this->createStub(PlatauHealthcheckStatusProvider::class);
        $provider->method('getCachedStatus')->willReturn($cachedStatus);

        return new PlatauHealthcheckExtension($deactivatePlatau, $noHealthcheck, $provider);
    }
}
