<?php

namespace App\Service\Commission;

use App\Dto\Commission\CalendarEventDto;
use App\Repository\DateCommissionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service pour gérer les événements du calendrier des commissions.
 */
final class CalendarService
{
    /** @var DateCommissionRepository */
    private $dateCommissionRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        DateCommissionRepository $dateCommissionRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->dateCommissionRepository = $dateCommissionRepository;
        $this->entityManager = $entityManager;
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

    /**
     * Parse une date JavaScript (toString) et extrait l'heure.
     * Format attendu : "Wed Dec 03 2025 17:55:00 GMT+0100 (heure normale d'Europe centrale)"
     * Retourne un DateTime avec uniquement l'heure (la date est aujourd'hui).
     *
     * @throws \InvalidArgumentException si le format est invalide
     */
    public function parseJavaScriptDateToTime(string $dateString): \DateTimeInterface
    {
        // Le format JS toString() : "Wed Dec 03 2025 17:55:00 GMT+0100 (nom timezone)"
        // On extrait l'heure avec une regex : HH:mm:ss
        if (!preg_match('/(\d{2}):(\d{2}):(\d{2})/', $dateString, $matches)) {
            throw new \InvalidArgumentException(sprintf('Format de date invalide : "%s"', $dateString));
        }

        $heure = $matches[1];
        $minute = $matches[2];
        $seconde = $matches[3];

        // Créer un DateTime avec uniquement l'heure (date du jour)
        $time = \DateTime::createFromFormat('H:i:s', sprintf('%s:%s:%s', $heure, $minute, $seconde));

        if (false === $time) {
            throw new \InvalidArgumentException(sprintf('Heure invalide extraite : %s:%s:%s', $heure, $minute, $seconde));
        }

        return $time;
    }

    /**
     * Modifie l'heure de fin d'une date de commission.
     *
     * @throws \InvalidArgumentException si la date de commission n'existe pas
     */
    public function updateHeureFin(int $idDateCommission, \DateTimeInterface $heureFin): void
    {
        $dateCommission = $this->dateCommissionRepository->find($idDateCommission);

        if (null === $dateCommission) {
            throw new \InvalidArgumentException(sprintf('Date de commission #%d introuvable', $idDateCommission));
        }

        $dateCommission->setHeureFin($heureFin);

        $this->entityManager->flush();
    }
}
