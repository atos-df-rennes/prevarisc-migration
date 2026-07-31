<?php

namespace App\Tests\Service\Commission;

use App\Dto\Commission\CommissionDatesDto;
use App\Dto\Commission\DateCommissionItemDto;
use App\Entity\Commission;
use App\Entity\CommissionTypeEvenement;
use App\Entity\DateCommission;
use App\Service\Commission\CommissionDatesTransformer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class CommissionDatesTransformerTest extends TestCase
{
    private CommissionDatesTransformer $transformer;

    /** @var LoggerInterface */
    private MockObject $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->transformer = new CommissionDatesTransformer($this->logger);
    }

    /**
     * Test transformation d'une seule date sans périodicité.
     */
    public function testTransformToEntitiesWithSingleDateWithoutPeriodicity(): void
    {
        $dto = $this->createValidDto();

        $dateItem = new DateCommissionItemDto();
        $dateItem->setDate(new \DateTime('2026-01-20 00:00:00'));
        $dateItem->setHeureDebut(new \DateTime('2026-01-20 09:00:00'));
        $dateItem->setHeureFin(new \DateTime('2026-01-20 18:00:00'));

        $dto->addDate($dateItem);

        $result = $this->transformer->transformToEntities($dto);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(DateCommission::class, $result[0]);
        $this->assertSame('Test Commission', $result[0]->getLibelle());
        $this->assertEquals(new \DateTime('2026-01-20 00:00:00'), $result[0]->getDate());
        $this->assertEquals(new \DateTime('2026-01-20 09:00:00'), $result[0]->getHeureDebut());
        $this->assertEquals(new \DateTime('2026-01-20 18:00:00'), $result[0]->getHeureFin());
    }

    /**
     * Test transformation de plusieurs dates sans périodicité.
     */
    public function testTransformToEntitiesWithMultipleDatesWithoutPeriodicity(): void
    {
        $dto = $this->createValidDto();

        // Date 1
        $dateItem1 = new DateCommissionItemDto();
        $dateItem1->setDate(new \DateTime('2026-01-20 00:00:00'));
        $dateItem1->setHeureDebut(new \DateTime('2026-01-20 09:00:00'));
        $dateItem1->setHeureFin(new \DateTime('2026-01-20 18:00:00'));

        $dto->addDate($dateItem1);

        // Date 2
        $dateItem2 = new DateCommissionItemDto();
        $dateItem2->setDate(new \DateTime('2026-01-21 00:00:00'));
        $dateItem2->setHeureDebut(new \DateTime('2026-01-21 10:00:00'));
        $dateItem2->setHeureFin(new \DateTime('2026-01-21 17:00:00'));

        $dto->addDate($dateItem2);

        // Date 3
        $dateItem3 = new DateCommissionItemDto();
        $dateItem3->setDate(new \DateTime('2026-01-22 00:00:00'));
        $dateItem3->setHeureDebut(new \DateTime('2026-01-22 14:00:00'));
        $dateItem3->setHeureFin(new \DateTime('2026-01-22 16:00:00'));

        $dto->addDate($dateItem3);

        $result = $this->transformer->transformToEntities($dto);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        // Vérifier chaque date
        $this->assertEquals(new \DateTime('2026-01-20 00:00:00'), $result[0]->getDate());
        $this->assertEquals(new \DateTime('2026-01-20 09:00:00'), $result[0]->getHeureDebut());
        $this->assertEquals(new \DateTime('2026-01-20 18:00:00'), $result[0]->getHeureFin());

        $this->assertEquals(new \DateTime('2026-01-21 00:00:00'), $result[1]->getDate());
        $this->assertEquals(new \DateTime('2026-01-21 10:00:00'), $result[1]->getHeureDebut());
        $this->assertEquals(new \DateTime('2026-01-21 17:00:00'), $result[1]->getHeureFin());

        $this->assertEquals(new \DateTime('2026-01-22 00:00:00'), $result[2]->getDate());
        $this->assertEquals(new \DateTime('2026-01-22 14:00:00'), $result[2]->getHeureDebut());
        $this->assertEquals(new \DateTime('2026-01-22 16:00:00'), $result[2]->getHeureFin());
    }

    /**
     * @dataProvider providePeriodicityValues
     */
    public function testTransformToEntitiesWithPeriodicityWeekly(int $periodiciteWeeks, int $expectedCount): void
    {
        $dto = $this->createValidDto();
        $dto->setWithPeriodicite(true);
        $dto->setDateFin(new \DateTime('2026-03-31 00:00:00'));
        $dto->setPeriodicite($periodiciteWeeks);

        $dateItem = new DateCommissionItemDto();
        $dateItem->setDate(new \DateTime('2026-01-20 00:00:00'));
        $dateItem->setHeureDebut(new \DateTime('2026-01-20 09:00:00'));
        $dateItem->setHeureFin(new \DateTime('2026-01-20 18:00:00'));

        $dto->addDate($dateItem);

        $result = $this->transformer->transformToEntities($dto);

        $this->assertIsArray($result);
        $this->assertCount($expectedCount, $result);

        // Vérifier que toutes les dates ont les mêmes heures
        foreach ($result as $dateCommission) {
            $heureDebut = $dateCommission->getHeureDebut();
            $heureFin = $dateCommission->getHeureFin();

            $this->assertSame('09:00:00', $heureDebut->format('H:i:s'));
            $this->assertSame('18:00:00', $heureFin->format('H:i:s'));
        }

        // Vérifier l'intervalle entre les dates
        if ($expectedCount > 1) {
            $interval = $result[0]->getDate()->diff($result[1]->getDate());
            $this->assertSame($periodiciteWeeks * 7, $interval->days);
        }
    }

    /**
     * Fournit les différentes périodicités possibles.
     *
     * @return array<string, array<int>>
     */
    public static function providePeriodicityValues(): array
    {
        return [
            'periodicite 1 semaine' => [1, 11], // Du 20/01 au 31/03 = 11 dates espacées d'1 semaine
            'periodicite 2 semaines' => [2, 6], // Du 20/01 au 31/03 = 6 dates espacées de 2 semaines
            'periodicite 3 semaines' => [3, 4], // Du 20/01 au 31/03 = 4 dates espacées de 3 semaines
            'periodicite 4 semaines' => [4, 3], // Du 20/01 au 31/03 = 3 dates espacées de 4 semaines
        ];
    }

    /**
     * @dataProvider provideMissingInformation
     */
    public function testTransformToEntitiesWithMissingInformation(callable $dtoModifier, string $expectedErrorContext): void
    {
        $dto = $this->createValidDto();

        $dateItem = new DateCommissionItemDto();
        $dateItem->setDate(new \DateTime('2026-01-20 00:00:00'));
        $dateItem->setHeureDebut(new \DateTime('2026-01-20 09:00:00'));
        $dateItem->setHeureFin(new \DateTime('2026-01-20 18:00:00'));

        // Appliquer la modification qui rend le DTO invalide
        $dtoModifier($dto, $dateItem);

        $dto->addDate($dateItem);

        // Vérifier que le logger est appelé avec le bon contexte
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Informations manquantes'),
                $this->callback(fn ($context): bool => null === $context[$expectedErrorContext])
            );

        $result = $this->transformer->transformToEntities($dto);

        // Aucune entité ne devrait être créée
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    /**
     * Fournit les cas d'informations manquantes.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function provideMissingInformation(): array
    {
        return [
            'date manquante' => [
                function (CommissionDatesDto $dto, DateCommissionItemDto $dateItem): void {
                    // On utilise Reflection pour forcer la valeur à null sans passer par le setter
                    $reflection = new \ReflectionClass($dateItem);
                    $property = $reflection->getProperty('date');
                    $property->setValue($dateItem, null);
                },
                'date',
            ],
            'heure debut manquante' => [
                function (CommissionDatesDto $dto, DateCommissionItemDto $dateItem): void {
                    $reflection = new \ReflectionClass($dateItem);
                    $property = $reflection->getProperty('heureDebut');
                    $property->setValue($dateItem, null);
                },
                'heure_debut',
            ],
            'heure fin manquante' => [
                function (CommissionDatesDto $dto, DateCommissionItemDto $dateItem): void {
                    $reflection = new \ReflectionClass($dateItem);
                    $property = $reflection->getProperty('heureFin');
                    $property->setValue($dateItem, null);
                },
                'heure_fin',
            ],
            'type evenement manquant' => [
                function (CommissionDatesDto $dto, DateCommissionItemDto $dateItem): void {
                    $reflection = new \ReflectionClass($dto);
                    $property = $reflection->getProperty('typeEvenement');
                    $property->setValue($dto, null);
                },
                'type_evenement',
            ],
        ];
    }

    /**
     * Test cas avec périodicité mais plusieurs dates initiales (ne devrait pas générer de périodicité).
     */
    public function testTransformToEntitiesWithPeriodicityButMultipleDates(): void
    {
        $dto = $this->createValidDto();
        $dto->setWithPeriodicite(true);
        $dto->setDateFin(new \DateTime('2026-03-31 00:00:00'));
        $dto->setPeriodicite(1);

        // Ajouter 2 dates (la périodicité ne s'applique que si 1 seule date)
        $dateItem1 = new DateCommissionItemDto();
        $dateItem1->setDate(new \DateTime('2026-01-20 00:00:00'));
        $dateItem1->setHeureDebut(new \DateTime('2026-01-20 09:00:00'));
        $dateItem1->setHeureFin(new \DateTime('2026-01-20 18:00:00'));

        $dto->addDate($dateItem1);

        $dateItem2 = new DateCommissionItemDto();
        $dateItem2->setDate(new \DateTime('2026-01-21 00:00:00'));
        $dateItem2->setHeureDebut(new \DateTime('2026-01-21 09:00:00'));
        $dateItem2->setHeureFin(new \DateTime('2026-01-21 18:00:00'));

        $dto->addDate($dateItem2);

        $result = $this->transformer->transformToEntities($dto);

        // La périodicité ne devrait pas s'appliquer car il y a 2 dates initiales
        $this->assertCount(2, $result);
    }

    /**
     * Crée un DTO valide pour les tests.
     */
    private function createValidDto(): CommissionDatesDto
    {
        $commission = new Commission();
        $commission->setLibelle('Commission Test');

        $typeEvenement = new CommissionTypeEvenement();
        $typeEvenement->setLibelle('Type Test');

        $dto = new CommissionDatesDto();
        $dto->setLibelle('Test Commission');
        $dto->setCommission($commission);
        $dto->setTypeEvenement($typeEvenement);

        return $dto;
    }
}
