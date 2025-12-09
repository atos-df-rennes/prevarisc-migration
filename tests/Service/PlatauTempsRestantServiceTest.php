<?php

namespace App\Tests\Service;

use App\Service\PlatauTempsRestantService;
use PHPUnit\Framework\TestCase;

class PlatauTempsRestantServiceTest extends TestCase
{
    /** @var PlatauTempsRestantService */
    private $service;

    protected function setUp(): void
    {
        $this->service = new PlatauTempsRestantService();
    }

    public function testCalculateReturnNullWhenLimitDateIsNull(): void
    {
        $result = $this->service->calculate(null);

        $this->assertNull($result);
    }

    public function testCalculateDaysDifference(): void
    {
        $limitDate = new \DateTime((new \DateTime('now +5 days'))->format('Y-m-d'));

        $result = $this->service->calculate($limitDate);

        $this->assertSame('5 jours', $result);
    }

    public function testCalculateOneDayDifference(): void
    {
        $limitDate = new \DateTime((new \DateTime('now +1 day'))->format('Y-m-d'));

        $result = $this->service->calculate($limitDate);

        $this->assertSame('1 jour', $result);
    }

    public function testCalculateMonthsDifference(): void
    {
        $limitDate = new \DateTime((new \DateTime('now +2 months'))->format('Y-m-d'));

        $result = $this->service->calculate($limitDate);

        $this->assertSame('2 mois', $result);
    }

    public function testCalculateYearsDifference(): void
    {
        $limitDate = new \DateTime((new \DateTime('now +1 year'))->format('Y-m-d'));

        $result = $this->service->calculate($limitDate);

        $this->assertSame('1 an', $result);
    }

    public function testCalculateMultipleYearsDifference(): void
    {
        $limitDate = new \DateTime((new \DateTime('now +2 years'))->format('Y-m-d'));

        $result = $this->service->calculate($limitDate);

        $this->assertSame('2 ans', $result);
    }

    public function testCalculateCombinedDifference(): void
    {
        $limitDate = new \DateTime((new \DateTime('now +1 year +2 months +3 days'))->format('Y-m-d'));

        $result = $this->service->calculate($limitDate);

        $this->assertSame('1 an et 2 mois et 3 jours', $result);
    }

    public function testCalculateNegativeDifference(): void
    {
        $limitDate = new \DateTime((new \DateTime('now -5 days'))->format('Y-m-d'));

        $result = $this->service->calculate($limitDate);

        $this->assertSame('- 5 jours', $result);
    }

    public function testGetCouleurTempsRestantReturnsDefaultWhenPastDate(): void
    {
        $limitDate = new \DateTime((new \DateTime('now -1 day'))->format('Y-m-d'));

        $result = $this->service->getCouleurTempsRestant($limitDate);

        $this->assertSame('default', $result);
    }

    public function testGetCouleurTempsRestantReturnsSuccessWhenMoreThan6Months(): void
    {
        $limitDate = new \DateTime((new \DateTime('now +7 months'))->format('Y-m-d'));

        $result = $this->service->getCouleurTempsRestant($limitDate);

        $this->assertSame('success', $result);
    }

    public function testGetCouleurTempsRestantReturnsWarningWhenBetween3And6Months(): void
    {
        $limitDate = new \DateTime((new \DateTime('now +5 months'))->format('Y-m-d'));

        $result = $this->service->getCouleurTempsRestant($limitDate);

        $this->assertSame('warning', $result);
    }

    public function testGetCouleurTempsRestantReturnsImportantWhenLessThan3Months(): void
    {
        $limitDate = new \DateTime((new \DateTime('now +2 months'))->format('Y-m-d'));

        $result = $this->service->getCouleurTempsRestant($limitDate);

        $this->assertSame('important', $result);
    }
}
