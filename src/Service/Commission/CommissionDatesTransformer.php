<?php

namespace App\Service\Commission;

use App\Dto\Commission\CommissionDatesDto;
use App\Dto\Commission\DateCommissionItemDto;
use App\Entity\DateCommission;
use Psr\Log\LoggerInterface;

/**
 * Service pour transformer un DTO CommissionDates en entités DateCommission.
 */
final class CommissionDatesTransformer
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * Transforme un DTO en collection d'entités DateCommission.
     *
     * @return DateCommission[]
     */
    public function transformToEntities(CommissionDatesDto $dto): array
    {
        $dateCommissions = [];

        // Récupération des dates du DTO
        $datesDto = $dto->getDates()->toArray();

        // Gestion de la périodicité
        if (
            $dto->isWithPeriodicite()
            && $dto->getDateFin()
            && $dto->getPeriodicite()
            && 1 === count($datesDto)
        ) {
            $datesDto = $this->generatePeriodicDates($datesDto[0], $dto->getDateFin(), $dto->getPeriodicite());
        }

        // Création des entités DateCommission
        foreach ($datesDto as $dateDto) {
            try {
                $dateCommission = $this->createDateCommission($dateDto, $dto);
            } catch (\InvalidArgumentException) {
                continue;
            }

            $dateCommissions[] = $dateCommission;
        }

        return $dateCommissions;
    }

    /**
     * Crée une entité DateCommission depuis un DateCommissionItemDto.
     *
     * @throws \InvalidArgumentException si une information requise est manquante
     */
    private function createDateCommission(
        DateCommissionItemDto $dateDto,
        CommissionDatesDto $commissionDto
    ): DateCommission {
        $date = $dateDto->getDate();
        $heureDebut = $dateDto->getHeureDebut();
        $heureFin = $dateDto->getHeureFin();
        $typeEvenement = $commissionDto->getTypeEvenement();

        if (in_array(null, [$date, $heureDebut, $heureFin, $typeEvenement], true)) {
            $this->logger->error('Informations manquantes pour créer une date de commission.', [
                'date' => $date,
                'heure_debut' => $heureDebut,
                'heure_fin' => $heureFin,
                'type_evenement' => $typeEvenement
            ]);

            throw new \InvalidArgumentException("Les informations de date, d'heure et de type d'événement sont manquantes.");
        }

        $dateCommission = new DateCommission();

        $dateCommission->setCommission($commissionDto->getCommission());
        $dateCommission->setDate($date);
        $dateCommission->setHeureDebut($heureDebut);
        $dateCommission->setHeureFin($heureFin);
        $dateCommission->setTypeEvenement($typeEvenement);
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
        $baseDateDate = $baseDate->getDate();
        $baseDateHeureDebut = $baseDate->getHeureDebut();
        $baseDateHeureFin = $baseDate->getHeureFin();

        if (in_array(null, [$baseDateDate, $baseDateHeureDebut, $baseDateHeureFin], true)) {
            $this->logger->error('Informations manquantes pour générer périodiquement des dates.', [
                'base_date' => $baseDateDate,
                'base_date_heure_debut' => $baseDateHeureDebut,
                'base_date_heure_fin' => $baseDateHeureFin
            ]);

            return [];
        }

        $dates = [$baseDate];

        $currentDate = clone $baseDateDate;

        $interval = new \DateInterval('P'.($periodiciteWeeks * 7).'D');

        while (true) {
            $currentDate = $currentDate->add($interval);
            if ($currentDate > $dateFin) {
                break;
            }

            $newDateDto = new DateCommissionItemDto();
            $newDateDto->setDate(clone $currentDate);
            $newDateDto->setHeureDebut($baseDateHeureDebut);
            $newDateDto->setHeureFin($baseDateHeureFin);

            $dates[] = $newDateDto;
        }

        return $dates;
    }
}
