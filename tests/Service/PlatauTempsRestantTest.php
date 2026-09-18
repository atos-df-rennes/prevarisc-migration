<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\PlatauTempsRestant;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PlatauTempsRestantTest extends TestCase
{
    private PlatauTempsRestant $service;

    protected function setUp(): void
    {
        $this->service = new PlatauTempsRestant();
    }

    #[DataProvider('calculateProvider')]
    public function testCalculate(string $dateModifier, string $expectedResult): void
    {
        $limitDate = new \DateTime(new \DateTime($dateModifier)->format('Y-m-d'));

        $result = $this->service->calculate($limitDate);

        $this->assertSame($expectedResult, $result);
    }

    public static function calculateProvider(): \Iterator
    {
        yield 'future limit date days' => ['now +5 days', '5 jours'];
        yield 'future limit date one day' => ['now +1 day', '1 jour'];
        yield 'future limit date months' => ['now +2 months', '2 mois'];
        yield 'future limit date years' => ['now +1 year', '1 an'];
        yield 'future limit date multiple years' => ['now +2 years', '2 ans'];
        yield 'future limit date combined' => ['now +1 year +2 months +3 days', '1 an et 2 mois et 3 jours'];
        yield 'past limit date days' => ['now -5 days', '- 5 jours'];
    }

    #[DataProvider('getCouleurTempsRestantProvider')]
    public function testGetCouleurTempsRestant(string $dateModifier, string $expectedColor): void
    {
        $limitDate = new \DateTime(new \DateTime($dateModifier)->format('Y-m-d'));

        $result = $this->service->getCouleurTempsRestant($limitDate);

        $this->assertSame($expectedColor, $result);
    }

    public static function getCouleurTempsRestantProvider(): \Iterator
    {
        yield 'past date returns default' => ['now -1 day', 'default'];
        yield 'more than 6 months returns success' => ['now +7 months', 'success'];
        yield 'between 3 and 6 months returns warning' => ['now +5 months', 'warning'];
        yield 'less than 3 months returns important' => ['now +2 months', 'danger'];
    }
}
