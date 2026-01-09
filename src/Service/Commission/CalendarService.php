<?php

namespace App\Service\Commission;

use App\Dto\Commission\CalendarEventDto;
use App\Repository\DateCommissionRepository;

/**
 * Service pour gérer les événements du calendrier des commissions.
 */
final class CalendarService
{
    /** @var DateCommissionRepository */
    private $dateCommissionRepository;

    public function __construct(DateCommissionRepository $dateCommissionRepository)
    {
        $this->dateCommissionRepository = $dateCommissionRepository;
    }

    /**
     * Récupère les événements du calendrier entre deux dates.
     *
     * @return CalendarEventDto[]
     */
    public function getEvents(
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?int $idCommission = null,
        ?int $idTypeEvenement = null
    ): array {
        $dateCommissions = $this->dateCommissionRepository->findEventsBetweenDates(
            $dateDebut,
            $dateFin,
            $idCommission,
            $idTypeEvenement
        );

        return array_map([CalendarEventDto::class, 'fromEntity'], $dateCommissions);
    }

    /**
     * Parse un timestamp JavaScript (millisecondes) en DateTime.
     */
    public function parseTimestamp(string $timestamp): ?\DateTimeInterface
    {
        $timestampSeconds = (int) substr($timestamp, 0, -3);

        if (0 === $timestampSeconds) {
            return null;
        }

        $date = new \DateTime();
        $date->setTimestamp($timestampSeconds);

        return $date;
    }
}
