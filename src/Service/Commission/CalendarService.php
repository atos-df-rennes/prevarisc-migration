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
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Service pour gérer les événements du calendrier des commissions.
 */
final readonly class CalendarService
{
    public function __construct(private DateCommissionRepository $dateCommissionRepository, private CommissionTypeEvenementRepository $commissionTypeEvenementRepository, private CommissionRepository $commissionRepository, private EntityManagerInterface $entityManager)
    {
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
    public function parseJavaScriptDateToTime(string $dateString): \DateTime
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
    public function parseJavaScriptDate(string $dateString): \DateTime
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
     * @throws \InvalidArgumentException si la commission ou le type d'événement n'existe pas
     */
    public function createDatesDtoFromRequest(Request $request): CommissionDatesDto
    {
        /** @var string|null $libelle */
        $libelle = $request->query->get('libelle');

        // Propriétés générales.
        $dateCommissionDto = new CommissionDatesDto();
        $dateCommissionDto->setLibelle($libelle);

        $idCommission = $request->query->getInt('commission');
        $commission = $this->commissionRepository->find($idCommission);
        if (null === $commission) {
            throw new \InvalidArgumentException(sprintf('Commission #%d introuvable', $idCommission));
        }

        $dateCommissionDto->setCommission($commission);

        $idTypeEvenement = $request->query->getInt('typeEvenement');
        $typeEvenement = $this->commissionTypeEvenementRepository->find($idTypeEvenement);
        if (null === $typeEvenement) {
            throw new \InvalidArgumentException(sprintf('Type événement #%d introuvable', $idTypeEvenement));
        }

        $dateCommissionDto->setTypeEvenement($typeEvenement);

        $start = $request->query->get('start');
        $end = $request->query->get('end');

        if (!\is_string($start) || !\is_string($end)) {
            throw new \InvalidArgumentException("Le format des dates n'est pas valide.");
        }

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
     * @param DateCommission[] $datesCommission
     *
     * @throws EntityNotFoundException si la date de commission n'existe pas
     */
    public function sauvegarderDatesCommission(array $datesCommission, bool $withPeriodicite): int
    {
        // On enregistre la 1ère date et on flush pour pouvoir récupérer son identifiant pour les éventuelles dates liées.
        $this->entityManager->persist($datesCommission[0]);
        $this->entityManager->flush();

        $idPremiereDate = $datesCommission[0]->getId();

        if (null === $idPremiereDate) {
            throw new EntityNotFoundException("La date de commission n'existe pas.");
        }

        foreach ($datesCommission as $index => $dateCommission) {
            // On vient d'enregistrer la 1ère date, on ne le refait donc pas.
            if (0 === $index) {
                continue;
            }

            /* Si la checkbox périodicité n'est pas cochée et qu'on a plusieurs dates,
             * c'est qu'on a fait une sélection multiple et que les dates sont liées.
             */
            if (!$withPeriodicite) {
                $dateCommission->setDateCommissionLiees($idPremiereDate);
            }

            $this->entityManager->persist($dateCommission);
        }

        $this->entityManager->flush();

        return $idPremiereDate;
    }

    /**
     * Met à jour le libellé d'une date de commission et de toutes ses dates liées.
     * Port de dateCommUpdateLibelle.
     */
    public function updateLibelle(int $idDateCommission, string $libelle): void
    {
        $this->dateCommissionRepository->updateLibelle($idDateCommission, $libelle);
    }

    /**
     * Met à jour le type d'une date de commission et de toutes ses dates liées.
     * Port de dateCommUpdateType.
     */
    public function updateType(int $idDateCommission, int $idNewType): void
    {
        $this->dateCommissionRepository->updateType($idDateCommission, $idNewType);
    }

    /**
     * Met à jour la date, l'heure de début et l'heure de fin d'une date de commission.
     * Port de valid_dateCom.
     *
     * @throws \InvalidArgumentException si la DateCommission n'existe pas ou si les formats sont invalides
     */
    public function updateDateCommission(int $idDateCommission, string $dateStr, string $heureD, string $heureFin): DateCommission
    {
        $dateCommission = $this->dateCommissionRepository->find($idDateCommission);

        if (null === $dateCommission) {
            throw new \InvalidArgumentException(sprintf('Date de commission #%d introuvable', $idDateCommission));
        }

        $date = \DateTime::createFromFormat('d/m/Y', $dateStr);
        $dateErrors = \DateTime::getLastErrors();
        if (false === $date || (false !== $dateErrors && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
            throw new \InvalidArgumentException(sprintf('Format de date invalide : "%s". Format attendu : dd/mm/yyyy', $dateStr));
        }

        $heureDebutObj = \DateTime::createFromFormat('H:i', $heureD);
        if (false === $heureDebutObj) {
            throw new \InvalidArgumentException(sprintf('Format d\'heure de début invalide : "%s". Format attendu : HH:mm', $heureD));
        }

        $heureFinObj = \DateTime::createFromFormat('H:i', $heureFin);
        if (false === $heureFinObj) {
            throw new \InvalidArgumentException(sprintf('Format d\'heure de fin invalide : "%s". Format attendu : HH:mm', $heureFin));
        }

        $dateCommission->setDate($date);
        $dateCommission->setHeureDebut($heureDebutObj);
        $dateCommission->setHeureFin($heureFinObj);

        $this->entityManager->flush();

        $typeEvenement = $dateCommission->getTypeEvenement();
        if (null !== $typeEvenement && null !== $typeEvenement->getId()) {
            $this->dateCommissionRepository->updateDependingDossierDates(
                $idDateCommission,
                $date->format('Y-m-d'),
                (int) $typeEvenement->getId()
            );
        }

        return $dateCommission;
    }

    /**
     * Supprime une date de commission liée (non-maître).
     * Port de supp_dateCom.
     * Retourne false si la date est maître (non supprimée), true si supprimée.
     */
    public function deleteLinkedDate(int $idDateCommission): bool
    {
        $dateCommission = $this->dateCommissionRepository->find($idDateCommission);

        if (null === $dateCommission) {
            return false;
        }

        if (null === $dateCommission->getDateCommissionLiees()) {
            return false;
        }

        $this->entityManager->remove($dateCommission);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Ajoute une date liée à une commission existante.
     * Port de addDateS.
     *
     * @throws \InvalidArgumentException si la date de référence n'existe pas ou si le format de date est invalide
     */
    public function addLinkedDate(int $idDateCommissionLiee, string $dateStr): DateCommission
    {
        $dateRef = $this->dateCommissionRepository->find($idDateCommissionLiee);

        if (null === $dateRef) {
            throw new \InvalidArgumentException(sprintf('Date de commission #%d introuvable', $idDateCommissionLiee));
        }

        $idUtile = $dateRef->getDateCommissionLiees() ?? $dateRef->getId();

        /** @var int $idUtile */
        $master = $this->dateCommissionRepository->find($idUtile);

        if (null === $master) {
            throw new \InvalidArgumentException(sprintf('Date de commission maître #%d introuvable', $idUtile));
        }

        $date = \DateTime::createFromFormat('d/m/Y', $dateStr);
        $dateErrors = \DateTime::getLastErrors();
        if (false === $date || (false !== $dateErrors && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
            throw new \InvalidArgumentException(sprintf('Format de date invalide : "%s". Format attendu : dd/mm/yyyy', $dateStr));
        }

        $newDate = new DateCommission();
        $newDate->setDate($date);
        $newDate->setDateCommissionLiees((int) $idUtile);

        $typeEvenement = $master->getTypeEvenement();
        if (null !== $typeEvenement) {
            $newDate->setTypeEvenement($typeEvenement);
        }

        $commission = $master->getCommission();
        if (null !== $commission) {
            $newDate->setCommission($commission);
        }

        $newDate->setLibelle($master->getLibelle());

        $heureDebut = $master->getHeureDebut();
        if (null !== $heureDebut) {
            $newDate->setHeureDebut($heureDebut);
        }

        $heureFin = $master->getHeureFin();
        if (null !== $heureFin) {
            $newDate->setHeureFin($heureFin);
        }

        $newDate->setGestionHeures(false);

        $this->entityManager->persist($newDate);
        $this->entityManager->flush();

        return $newDate;
    }

    /**
     * Rend une date de commission maître parmi les dates liées (makeDefaut).
     * Port de makeDefaut.
     *
     * @throws \InvalidArgumentException si la date de commission n'existe pas
     */
    public function changeMasterDate(int $idDateCommission): void
    {
        $dateCommission = $this->dateCommissionRepository->find($idDateCommission);

        if (null === $dateCommission) {
            throw new \InvalidArgumentException(sprintf('Date de commission #%d introuvable', $idDateCommission));
        }

        $oldMasterId = $dateCommission->getDateCommissionLiees();

        if (null === $oldMasterId) {
            return;
        }

        $this->dateCommissionRepository->changeMasterDateComm((int) $oldMasterId, $idDateCommission);

        $dateCommission->setDateCommissionLiees(null);
        $this->entityManager->flush();
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
