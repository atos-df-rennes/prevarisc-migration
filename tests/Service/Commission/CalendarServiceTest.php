<?php

namespace App\Tests\Service\Commission;

use App\Repository\CommissionRepository;
use App\Repository\CommissionTypeEvenementRepository;
use App\Repository\DateCommissionRepository;
use App\Service\Commission\CalendarService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class CalendarServiceTest extends TestCase
{
    /** @var CalendarService */
    private $calendarService;

    protected function setUp(): void
    {
        $dateCommissionRepository = $this->createMock(DateCommissionRepository::class);
        $commissionTypeEvenementRepository = $this->createMock(CommissionTypeEvenementRepository::class);
        $commissionRepository = $this->createMock(CommissionRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->calendarService = new CalendarService(
            $dateCommissionRepository,
            $commissionTypeEvenementRepository,
            $commissionRepository,
            $entityManager
        );
    }

    /**
     * @dataProvider provideTimestampData
     */
    public function testParseTimestamp(string $timestamp, ?\DateTimeInterface $expectedResult): void
    {
        $result = $this->calendarService->parseTimestamp($timestamp);

        if (null === $expectedResult) {
            $this->assertNull($result);
        } else {
            $this->assertInstanceOf(\DateTimeInterface::class, $result);
            $this->assertSame($expectedResult->getTimestamp(), $result->getTimestamp());
        }
    }

    /**
     * Fournit les données pour les tests de parseTimestamp.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function provideTimestampData(): array
    {
        return [
            'timestamp valide (millisecondes)' => [
                '1642611600000', // 19 janvier 2022 15:00:00 UTC
                (new \DateTime())->setTimestamp(1642611600),
            ],
            'timestamp invalide (zeros)' => [
                '0000000000000',
                null,
            ],
            'timestamp invalide (vide avec zeros)' => [
                '000',
                null,
            ],
        ];
    }

    /**
     * @dataProvider provideJavaScriptDateToTimeData
     *
     * @param string $expectedTime Format H:i:s
     */
    public function testParseJavaScriptDateToTime(string $dateString, string $expectedTime, bool $shouldThrowException = false): void
    {
        if ($shouldThrowException) {
            $this->expectException(\InvalidArgumentException::class);
        }

        $result = $this->calendarService->parseJavaScriptDateToTime($dateString);

        if (!$shouldThrowException) {
            $this->assertInstanceOf(\DateTime::class, $result);
            $this->assertSame($expectedTime, $result->format('H:i:s'));
        }
    }

    /**
     * Fournit les données pour les tests de parseJavaScriptDateToTime.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function provideJavaScriptDateToTimeData(): array
    {
        // Note: Les heures sont en UTC dans le format ISO, mais converties en timezone locale
        // Avec Europe/Paris (UTC+1), 08:00:00Z devient 09:00:00
        return [
            'format valide avec millisecondes' => [
                '2026-01-20T08:00:00.000Z',
                '09:00:00', // UTC+1
                false,
            ],
            'format valide sans millisecondes' => [
                '2026-01-20T16:30:00Z',
                '17:30:00', // UTC+1
                false,
            ],
            'format de date invalide' => [
                '2026/01/20T08:00:00.000Z',
                '',
                true,
            ],
            'format complètement invalide' => [
                'invalid-date-string',
                '',
                true,
            ],
        ];
    }

    /**
     * @dataProvider provideJavaScriptDateData
     *
     * @param string $expectedDate Format Y-m-d H:i:s
     */
    public function testParseJavaScriptDate(string $dateString, string $expectedDate, bool $shouldThrowException = false): void
    {
        if ($shouldThrowException) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessageRegExp('/Format de date invalide/');
        }

        $result = $this->calendarService->parseJavaScriptDate($dateString);

        if (!$shouldThrowException) {
            $this->assertInstanceOf(\DateTime::class, $result);
            $this->assertSame($expectedDate, $result->format('Y-m-d H:i:s'));
        }
    }

    /**
     * Fournit les données pour les tests de parseJavaScriptDate.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function provideJavaScriptDateData(): array
    {
        // Note: Les dates sont en UTC dans le format ISO, converties en timezone locale
        // Avec Europe/Paris (UTC+1), les heures sont décalées de +1h
        return [
            'format valide avec millisecondes' => [
                '2026-01-20T08:00:00.000Z',
                '2026-01-20 09:00:00', // UTC+1
                false,
            ],
            'format valide sans millisecondes' => [
                '2026-01-20T14:30:00Z',
                '2026-01-20 15:30:00', // UTC+1
                false,
            ],
            'format de date invalide (slashes)' => [
                '2026/01/20T08:00:00.000Z',
                '',
                true,
            ],
            'format complètement invalide' => [
                'not-a-valid-date',
                '',
                true,
            ],
            'format sans timezone' => [
                '2026-01-20T08:00:00',
                '',
                true,
            ],
        ];
    }
}
