<?php

namespace App\Service\Commission;

use App\Dto\Commission\CalendarEventDto;
use App\Dto\Commission\CommissionDatesDto;
use App\Dto\Commission\DateCommissionItemDto;
use App\Entity\CommissionTypeEvenement;
use App\Entity\DateCommission;
use App\Repository\CommissionRepository;
use App\Repository\CommissionTypeEvenementRepository;
use App\Repository\DateCommissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Service pour gérer les événements du calendrier des commissions.
 */
final class CalendarService
{
    /** @var DateCommissionRepository */
    private $dateCommissionRepository;

    /** @var CommissionTypeEvenementRepository */
    private $commissionTypeEvenementRepository;

    /** @var CommissionRepository */
    private $commissionRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        DateCommissionRepository $dateCommissionRepository,
        CommissionTypeEvenementRepository $commissionTypeEvenementRepository,
        CommissionRepository $commissionRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->dateCommissionRepository = $dateCommissionRepository;
        $this->commissionTypeEvenementRepository = $commissionTypeEvenementRepository;
        $this->commissionRepository = $commissionRepository;
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
     * Créer un DTO avec toutes les informations requises pour le formulaire de création d'une ou plusieurs dates de commission.
     * La requête contient les éléments saisis dans le formulaire du dossier (Libellé, Commission et TypeEvenement)
     * et des événements saisis sur le calendrier (Dates saisies par l'utilisateur).
     *
     * @throws \InvalidArgumentException Si la commission ou le type d'événement n'existe pas.
     */
    public function createDatesDtoFromRequest(Request $request): CommissionDatesDto
    {
        // Propriétés générales.
        $dateCommissionDto = new CommissionDatesDto();
        $dateCommissionDto->setLibelle($request->query->get('libelle'));

        $idCommission = $request->query->getInt('commission');
        $commission = $this->commissionRepository->find($idCommission);
        if (null === $commission) {
            throw new \InvalidArgumentException(sprintf('Commission #%d introuvable', $idCommission));
        }
        $dateCommissionDto->setCommission($this->commissionRepository->find($idCommission) );

        $idTypeEvenement = $request->query->getInt('typeEvenement');
        $typeEvenement = $this->commissionTypeEvenementRepository->find($idTypeEvenement);
        if (null === $typeEvenement) {
            throw new \InvalidArgumentException(sprintf('Type événement #%d introuvable', $idTypeEvenement));
        }
        $dateCommissionDto->setTypeEvenement($this->commissionTypeEvenementRepository->find($idTypeEvenement));

        $start = $request->query->get('start');
        $end = $request->query->get('end');

        // Conversion de la date FullCalendar en DateTimeInterface.
        $dateStart = $this->parseJavaScriptDate($start);
        $dateEnd = $this->parseJavaScriptDate($end);

        // Ajout de la 1ère date.
        $dateCommissionItem = new DateCommissionItemDto($dateStart);
        $dateCommissionDto->addDate($dateCommissionItem);

        // Ajout des dates suivantes si sélection multiple.
        $numberOfAdditionalDates = $dateStart->diff($dateEnd)->days;
        if (\is_int($numberOfAdditionalDates)) {
            for ($i = 1; $i <= $numberOfAdditionalDates; ++$i) {
                $date = clone $dateStart;
                $date->modify(\sprintf('+%d days', $i));

                $dateCommissionItem = new DateCommissionItemDto($date);
                $dateCommissionDto->addDate($dateCommissionItem);
            }
        }

        return $dateCommissionDto;
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
