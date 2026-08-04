<?php

namespace App\Tests\Service;

use App\Service\PlatauTempsRestant;
use PHPUnit\Framework\TestCase;

class PlatauTempsRestantTest extends TestCase
{
    private PlatauTempsRestant $service;

    protected function setUp(): void
    {
        $this->service = new PlatauTempsRestant();
    }

    /**
     * @dataProvider calculateProvider
     */
    public function testCalculate(string $dateModifier, string $expectedResult): void
    {
        $limitDate = new \DateTime(new \DateTime($dateModifier)->format('Y-m-d'));

        $result = $this->service->calculate($limitDate);

        $this->assertSame($expectedResult, $result);
    }

    public static function calculateProvider(): array
    {
        return [
            'future limit date days' => ['now +5 days', '5 jours'],
            'future limit date one day' => ['now +1 day', '1 jour'],
            'future limit date months' => ['now +2 months', '2 mois'],
            'future limit date years' => ['now +1 year', '1 an'],
            'future limit date multiple years' => ['now +2 years', '2 ans'],
            'future limit date combined' => ['now +1 year +2 months +3 days', '1 an et 2 mois et 3 jours'],
            'past limit date days' => ['now -5 days', '- 5 jours'],
        ];
    }

    /**
     * @dataProvider getCouleurTempsRestantProvider
     */
    public function testGetCouleurTempsRestant(string $dateModifier, string $expectedColor): void
    {
        $limitDate = new \DateTime(new \DateTime($dateModifier)->format('Y-m-d'));

        $result = $this->service->getCouleurTempsRestant($limitDate);

        $this->assertSame($expectedColor, $result);
    }

    public static function getCouleurTempsRestantProvider(): array
    {
        return [
            'past date returns default' => ['now -1 day', 'default'],
            'more than 6 months returns success' => ['now +7 months', 'success'],
            'between 3 and 6 months returns warning' => ['now +5 months', 'warning'],
            'less than 3 months returns important' => ['now +2 months', 'danger'],
        ];
    }
}
