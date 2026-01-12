<?php

namespace App\Service\Commission;

use App\Dto\Commission\CommissionDatesDto;
use App\Dto\Commission\DateCommissionItemDto;
use App\Entity\Commission;
use App\Entity\DateCommission;

/**
 * Service pour transformer un DTO CommissionDates en entités DateCommission.
 */
class CommissionDatesTransformer
{
    /**
     * Transforme un DTO en collection d'entités DateCommission.
     *
     * @return DateCommission[]
     */
    public function transformToEntities(CommissionDatesDto $dto, Commission $commission): array
    {
        $dateCommissions = [];

        // Récupération des dates du DTO
        $datesDto = $dto->getDates()->toArray();

        // Gestion de la périodicité
        if ($dto->isWithPeriodicite() && $dto->getDateFin() && $dto->getPeriodicite() && count($datesDto) === 1) {
            $datesDto = $this->generatePeriodicDates($datesDto[0], $dto->getDateFin(), $dto->getPeriodicite());
        }

        // Création des entités DateCommission
        foreach ($datesDto as $dateDto) {
            $dateCommission = $this->createDateCommission($dateDto, $commission, $dto);
            $dateCommissions[] = $dateCommission;
        }

        return $dateCommissions;
    }

    /**
     * Crée une entité DateCommission depuis un DateCommissionItemDto.
     */
    private function createDateCommission(
        DateCommissionItemDto $dateDto,
        Commission $commission,
        CommissionDatesDto $commissionDto
    ): DateCommission {
        $dateCommission = new DateCommission();

        $dateCommission->setCommission($commission);
        $dateCommission->setDate($dateDto->getDate());
        $dateCommission->setHeureDebut($dateDto->getHeureDebut());
        $dateCommission->setHeureFin($dateDto->getHeureFin());
        $dateCommission->setTypeEvenement($commissionDto->getTypeEvenement());
        $dateCommission->setLibelle($commissionDto->getLibelle());
        $dateCommission->setGestionHeures(true);

        return $dateCommission;
    }

    /**
     * Génère les dates selon la périodicité.
     *
     * @return DateCommissionItemDto[]
     */
    private function generatePeriodicDates(
        DateCommissionItemDto $baseDate,
        \DateTimeInterface $dateFin,
        int $periodiciteWeeks
    ): array {
        $dates = [$baseDate];

        $currentDate = clone $baseDate->getDate();
        if (!$currentDate) {
            return $dates;
        }

        $interval = new \DateInterval('P' . ($periodiciteWeeks * 7) . 'D');

        while (true) {
            $currentDate = $currentDate->add($interval);
            if ($currentDate > $dateFin) {
                break;
            }

            $newDateDto = new DateCommissionItemDto();
            $newDateDto->setDate(clone $currentDate);
            $newDateDto->setHeureDebut($baseDate->getHeureDebut());
            $newDateDto->setHeureFin($baseDate->getHeureFin());

            $dates[] = $newDateDto;
        }

        return $dates;
    }
}
