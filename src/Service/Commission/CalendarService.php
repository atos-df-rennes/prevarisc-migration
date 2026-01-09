<?php

namespace App\Service\Commission;

use App\Dto\Commission\CalendarEventDto;
use App\Entity\CommissionTypeEvenement;
use App\Entity\DateCommission;
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
     * Parse une date JavaScript (ISO 8601) et extrait l'heure en timezone locale.
     * Format attendu : "2026-01-09T07:00:00.000Z" (UTC)
     * Retourne un DateTime avec uniquement l'heure convertie en timezone locale.
     *
     * @throws \InvalidArgumentException si le format est invalide
     */
    public function parseJavaScriptDateToTime(string $dateString): \DateTimeInterface
    {
        // Avec toISOString(), on reçoit maintenant du ISO 8601 en UTC
        // Il faut parser la date complète puis extraire l'heure en timezone locale

        // Parser la date ISO 8601 en UTC
        $date = \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $dateString, new \DateTimeZone('UTC'));

        if (false === $date) {
            // Essayer sans millisecondes
            $date = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $dateString, new \DateTimeZone('UTC'));
        }

        if (false === $date) {
            throw new \InvalidArgumentException(sprintf('Format de date invalide : "%s"', $dateString));
        }

        // Convertir en timezone locale pour obtenir l'heure correcte
        $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        // Créer un DateTime avec uniquement l'heure locale (date du jour)
        $time = \DateTime::createFromFormat('H:i:s', $date->format('H:i:s'));

        if (false === $time) {
            throw new \InvalidArgumentException(sprintf('Heure invalide extraite : %s', $date->format('H:i:s')));
        }

        return $time;
    }

    /**
     * Parse une date au format ISO 8601 (format standard JavaScript toISOString()).
     * Format requis : "2026-01-09T09:00:00.000Z"
     * Ce format est garanti par l'appel à toISOString() dans calendar-init.js.
     *
     * Note : Le Z indique UTC, mais FullCalendar envoie déjà l'heure en timezone locale
     * puis la convertit en UTC. On doit donc reconvertir vers le timezone local pour
     * obtenir l'heure d'origine saisie par l'utilisateur.
     *
     * @throws \InvalidArgumentException si le format n'est pas ISO 8601 valide
     */
    public function parseJavaScriptDate(string $dateString): \DateTimeInterface
    {
        // Format ISO 8601 avec millisecondes et Z (UTC) - format FullCalendar standard
        // Exemple : "2026-01-09T09:00:00.000Z"
        $date = \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $dateString, new \DateTimeZone('UTC'));

        if (false !== $date) {
            // Convertir UTC vers le timezone local configuré
            $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));

            return $date;
        }

        // Format ISO 8601 sans millisecondes (fallback)
        $date = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $dateString, new \DateTimeZone('UTC'));

        if (false !== $date) {
            $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));

            return $date;
        }

        throw new \InvalidArgumentException(sprintf('Format de date invalide : "%s". Format attendu : ISO 8601 (ex: "2026-01-09T09:00:00.000Z"). Vérifiez que toISOString() est utilisé côté JavaScript.', $dateString));
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

    /**
     * Déplace une date de commission (date, heure de début et heure de fin).
     *
     * @throws \InvalidArgumentException si la date de commission n'existe pas ou si les paramètres sont invalides
     */
    public function deplacerDateCommission(
        int $idDateCommission,
        \DateTimeInterface $nouvelleDate,
        ?\DateTimeInterface $nouvelleHeureDebut = null,
        ?\DateTimeInterface $nouvelleHeureFin = null
    ): void {
        $dateCommission = $this->dateCommissionRepository->find($idDateCommission);

        if (null === $dateCommission) {
            throw new \InvalidArgumentException(sprintf('Date de commission #%d introuvable', $idDateCommission));
        }

        $dateCommission->setDate($nouvelleDate);

        if (null !== $nouvelleHeureDebut) {
            $dateCommission->setHeureDebut($nouvelleHeureDebut);
        }

        if (null !== $nouvelleHeureFin) {
            $dateCommission->setHeureFin($nouvelleHeureFin);
        }

        $this->entityManager->flush();

        // Mise à jour des dates des dossiers liés (iso-fonctionnel avec le legacy)
        $this->updateDependingDossierDates($dateCommission);
    }

    /**
     * Met à jour les dates des dossiers liés à une date de commission.
     * Iso-fonctionnel avec Model_DbTable_DateCommission::updateDependingDossierDates().
     */
    private function updateDependingDossierDates(DateCommission $dateCommission): void
    {
        $dossierAffectations = $dateCommission->getDossierAffectations();

        if ($dossierAffectations->isEmpty()) {
            return;
        }

        $typeEvenementId = $dateCommission->getTypeEvenement() instanceof CommissionTypeEvenement ? $dateCommission->getTypeEvenement()->getId() : null;
        $nouvelleDate = $dateCommission->getDate();

        foreach ($dossierAffectations as $dossierAffectation) {
            $dossier = $dossierAffectation->getDossier();
            if (null === $dossier) {
                continue;
            }

            if (null === $typeEvenementId) {
                continue;
            }

            if (null === $nouvelleDate) {
                continue;
            }

            // COMMISSION EN SALLE
            if (1 === $typeEvenementId) {
                // setDateCommission attend une string au format Y-m-d
                $dossier->setDateCommission($nouvelleDate->format('Y-m-d'));
            }
            // VISITE OU GROUPE DE VISITE
            elseif (in_array($typeEvenementId, [2, 3], true)) {
                // setDateVisite attend un DateTimeInterface
                $dossier->setDateVisite($nouvelleDate);
            }
        }

        $this->entityManager->flush();
    }
}
