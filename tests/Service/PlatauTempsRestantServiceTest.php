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
        $now = new \DateTime();
        $limitDate = (clone $now)->modify('+5 days');

        $result = $this->service->calculate($limitDate);

        $this->assertSame('5 jours', $result);
    }

    public function testCalculateOneDayDifference(): void
    {
        $now = new \DateTime();
        $limitDate = (clone $now)->modify('+1 day');

        $result = $this->service->calculate($limitDate);

        $this->assertSame('1 jour', $result);
    }

    public function testCalculateMonthsDifference(): void
    {
        $now = new \DateTime();
        $limitDate = (clone $now)->modify('+2 months');

        $result = $this->service->calculate($limitDate);

        $this->assertSame('2 mois', $result);
    }

    public function testCalculateYearsDifference(): void
    {
        $now = new \DateTime();
        $limitDate = (clone $now)->modify('+1 year');

        $result = $this->service->calculate($limitDate);

        $this->assertSame('1 an', $result);
    }

    public function testCalculateMultipleYearsDifference(): void
    {
        $now = new \DateTime();
        $limitDate = (clone $now)->modify('+2 years');

        $result = $this->service->calculate($limitDate);

        $this->assertSame('2 ans', $result);
    }

    public function testCalculateCombinedDifference(): void
    {
        $now = new \DateTime();
        $limitDate = (clone $now)->modify('+1 year +2 months +3 days');

        $result = $this->service->calculate($limitDate);

        $this->assertSame('1 an et 2 mois et 3 jours', $result);
    }

    public function testCalculateNegativeDifference(): void
    {
        $now = new \DateTime();
        $limitDate = (clone $now)->modify('-5 days');

        $result = $this->service->calculate($limitDate);

        $this->assertSame('- 5 jours', $result);
    }

    public function testGetCouleurTempsRestantReturnsInverseWhenPastDate(): void
    {
        $now = new \DateTime();
        $limitDate = (clone $now)->modify('-1 day');

        $result = $this->service->getCouleurTempsRestant($limitDate);

        $this->assertSame('inverse', $result);
    }

    public function testGetCouleurTempsRestantReturnsSuccessWhenMoreThan6Months(): void
    {
        $now = new \DateTime();
        $limitDate = (clone $now)->modify('+7 months');

        $result = $this->service->getCouleurTempsRestant($limitDate);

        $this->assertSame('success', $result);
    }

    public function testGetCouleurTempsRestantReturnsSuccessWhenMoreThan1Year(): void
    {
        $now = new \DateTime();
        $limitDate = (clone $now)->modify('+1 year');

        $result = $this->service->getCouleurTempsRestant($limitDate);

        $this->assertSame('success', $result);
    }

    public function testGetCouleurTempsRestantReturnsWarningWhenBetween3And6Months(): void
    {
        $now = new \DateTime();
        $limitDate = (clone $now)->modify('+5 months');

        $result = $this->service->getCouleurTempsRestant($limitDate);

        $this->assertSame('warning', $result);
    }

    public function testGetCouleurTempsRestantReturnsImportantWhenLessThan3Months(): void
    {
        $now = new \DateTime();
        $limitDate = (clone $now)->modify('+2 months');

        $result = $this->service->getCouleurTempsRestant($limitDate);

        $this->assertSame('important', $result);
    }

    public function testGetCouleurTempsRestantReturnsImportantWhenLessThan1Month(): void
    {
        $now = new \DateTime();
        $limitDate = (clone $now)->modify('+15 days');

        $result = $this->service->getCouleurTempsRestant($limitDate);

        $this->assertSame('important', $result);
    }
}
