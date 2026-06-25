<?php

declare(strict_types=1);

namespace App\Service\Commission;

use App\Constant\CommissionTypeEvenement;
use App\Constant\DossierType;
use App\Repository\DateCommissionRepository;
use Sabre\VObject\Component\VCalendar;

/**
 * Service de génération du flux iCalendar pour la synchronisation webcal.
 *
 * Port de Api_Service_Calendar — une VEVENT par couple DateCommission × Dossier,
 * avec SUMMARY et LOCATION enrichis (numéro ERP, établissement, adresse).
 * La DESCRIPTION inclut les préventionnistes du dossier.
 *
 * Note : les contacts, coordonnées de la mairie et avis d'exploitation
 * ne sont pas inclus dans la DESCRIPTION (requêtes supplémentaires non implémentées).
 */
class IcalendarSyncService
{
    /**
     * @var DateCommissionRepository
     */
    private $dateCommissionRepository;

    public function __construct(DateCommissionRepository $dateCommissionRepository)
    {
        $this->dateCommissionRepository = $dateCommissionRepository;
    }

    /**
     * Génère le flux iCalendar sérialisé.
     *
     * Port de Api_Service_Calendar::sync() :
     * - Une VEVENT par couple DateCommission × Dossier.
     * - SUMMARY enrichi : #NUMEROID libellé (commune) : type nature - objet (en salle)
     *   ou #NUMEROID libellé : libellé_séance (visite/groupe de visite).
     * - LOCATION : adresse de l'établissement (visite) ou nom de la commission (en salle).
     * - DESCRIPTION : préventionniste(s) du dossier.
     *
     * @param int|null $userId       ID préventionniste — null pour tout retourner (admin)
     * @param int|null $commissionId ID commission — null pour toutes les commissions
     *
     * @return string Le flux iCalendar sérialisé (text/calendar)
     */
    public function generateFeed(?int $userId, ?int $commissionId): string
    {
        $today = new \DateTime();
        $today->modify('-1 year');

        $fromDate = $today->format('Y-m-d');

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->dateCommissionRepository->findForWebcalSync($userId, $commissionId, $fromDate);

        $refreshTime = (string) (getenv('PREVARISC_CALENDAR_REFRESH_TIME') ?: 'PT5M');
        $calendrierNom = 'Prévarisc';

        $calendar = new VCalendar([
            'METHOD' => 'PUBLISH',
            'NAME' => $calendrierNom,
            'X-WR-CALNAME' => $calendrierNom,
            'X-WR-CALDESC' => 'Calendrier Prévarisc',
            'REFRESH-INTERVAL;VALUE=DURATION' => $refreshTime,
            'X-PUBLISHED-TTL' => $refreshTime,
        ]);

        $tzid = 'Europe/Paris';
        $this->addVTimezone($calendar, $tzid);

        $tz = new \DateTimeZone($tzid);
        $nowUtc = new \DateTime('now', new \DateTimeZone('UTC'));

        foreach ($rows as $row) {
            $dcId = isset($row['ID_DATECOMMISSION']) && is_numeric($row['ID_DATECOMMISSION'])
                ? (int) $row['ID_DATECOMMISSION']
                : 0;

            $dossierId = isset($row['ID_DOSSIER']) && is_numeric($row['ID_DOSSIER'])
                ? (int) $row['ID_DOSSIER']
                : null;

            $dateStr = \is_string($row['DATE_COMMISSION'] ?? null) ? $row['DATE_COMMISSION'] : '';
            $heureDebCom = \is_string($row['HEUREDEB_COMMISSION'] ?? null) ? $row['HEUREDEB_COMMISSION'] : '00:00:00';
            $heureFinCom = \is_string($row['HEUREFIN_COMMISSION'] ?? null) ? $row['HEUREFIN_COMMISSION'] : '00:00:00';

            // Utilise l'heure d'affectation du dossier si disponible, sinon l'heure de la séance
            $heureDebAffect = \is_string($row['HEURE_DEB_AFFECT'] ?? null) ? $row['HEURE_DEB_AFFECT'] : '';
            $heureFinAffect = \is_string($row['HEURE_FIN_AFFECT'] ?? null) ? $row['HEURE_FIN_AFFECT'] : '';
            $heureDebStr = ($dossierId && '' !== $heureDebAffect) ? $heureDebAffect : $heureDebCom;
            $heureFinStr = ($dossierId && '' !== $heureFinAffect) ? $heureFinAffect : $heureFinCom;

            try {
                $dtStart = new \DateTime($dateStr.' '.$heureDebStr, $tz);
                $dtEnd = new \DateTime($dateStr.' '.$heureFinStr, $tz);
            } catch (\Exception $e) {
                continue;
            }

            if (null === $dossierId) {
                // DateCommission sans dossier : VEVENT minimal
                $libelleDate = \is_string($row['LIBELLE_DATECOMMISSION'] ?? null) ? $row['LIBELLE_DATECOMMISSION'] : '';
                $libelleCommission = \is_string($row['LIBELLE_COMMISSION'] ?? null) ? $row['LIBELLE_COMMISSION'] : '';
                $summary = $libelleDate.('' !== $libelleCommission ? ' - '.$libelleCommission : '');

                $calendar->add('VEVENT', [
                    'SUMMARY' => $summary,
                    'DTSTART' => $dtStart,
                    'DTEND' => $dtEnd,
                    'DESCRIPTION' => '',
                    'LOCATION' => '',
                    'UID' => sprintf('prevarisc-commission-%d@prevarisc.local', $dcId),
                    'DTSTAMP' => $nowUtc,
                ]);

                continue;
            }

            [$summary, $location] = $this->buildSummaryAndLocation($row);
            $description = $this->buildDescription($row);

            $calendar->add('VEVENT', [
                'SUMMARY' => $summary,
                'DTSTART' => $dtStart,
                'DTEND' => $dtEnd,
                'DESCRIPTION' => $description,
                'LOCATION' => $location,
                'UID' => sprintf('prevarisc-commission-%d-dossier-%d@prevarisc.local', $dcId, $dossierId),
                'DTSTAMP' => $nowUtc,
            ]);
        }

        return $calendar->serialize();
    }

    /**
     * Construit le résumé et la localisation du VEVENT à partir des données de la ligne.
     *
     * Port de Api_Service_Calendar::createICSEvent() — sections SUMMARY et LOCATION.
     *
     * @param array<string, mixed> $row
     *
     * @return array{0: string, 1: string}
     */
    private function buildSummaryAndLocation(array $row): array
    {
        $numeroid = \is_string($row['NUMEROID_ETABLISSEMENT'] ?? null) ? $row['NUMEROID_ETABLISSEMENT'] : '';
        $libelleEts = \is_string($row['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? null) ? $row['LIBELLE_ETABLISSEMENTINFORMATIONS'] : '';
        $commune = \is_string($row['LIBELLE_COMMUNE'] ?? null) ? $row['LIBELLE_COMMUNE'] : '';
        $libelleDate = \is_string($row['LIBELLE_DATECOMMISSION'] ?? null) ? $row['LIBELLE_DATECOMMISSION'] : '';
        $libelleCommission = \is_string($row['LIBELLE_COMMISSION'] ?? null) ? $row['LIBELLE_COMMISSION'] : '';
        $libelleType = \is_string($row['LIBELLE_DOSSIERTYPE'] ?? null) ? $row['LIBELLE_DOSSIERTYPE'] : '';
        $libelleNature = \is_string($row['LIBELLE_DOSSIERNATURE'] ?? null) ? $row['LIBELLE_DOSSIERNATURE'] : '';
        $objetDossier = \is_string($row['OBJET_DOSSIER'] ?? null) ? $row['OBJET_DOSSIER'] : '';
        $typeEvenement = isset($row['ID_COMMISSIONTYPEEVENEMENT']) && is_numeric($row['ID_COMMISSIONTYPEEVENEMENT'])
            ? (int) $row['ID_COMMISSIONTYPEEVENEMENT']
            : 0;
        $typeDossier = isset($row['TYPE_DOSSIER']) && is_numeric($row['TYPE_DOSSIER'])
            ? (int) $row['TYPE_DOSSIER']
            : 0;

        if (CommissionTypeEvenement::TYPE_COMMISSION_SALLE === $typeEvenement) {
            $libelleSum = (DossierType::GROUPE_DE_VISITE === $typeDossier) ? $libelleDate : $objetDossier;
            $summary = substr(
                sprintf('#%s %s (%s) : %s %s - %s', $numeroid, $libelleEts, $commune, $libelleType, $libelleNature, trim($libelleSum)),
                0,
                255
            );
            $location = sprintf('Commission en salle de %s', $libelleCommission);
        } else {
            $summary = substr(
                sprintf('#%s %s : %s', $numeroid, $libelleEts, $libelleDate),
                0,
                255
            );
            $numero = \is_string($row['NUMERO_ADRESSE'] ?? null) ? $row['NUMERO_ADRESSE'] : '';
            $ruetype = \is_string($row['LIBELLE_RUETYPE'] ?? null) ? $row['LIBELLE_RUETYPE'] : '';
            $rue = \is_string($row['LIBELLE_RUE'] ?? null) ? $row['LIBELLE_RUE'] : '';
            $cp = \is_string($row['CODEPOSTAL_COMMUNE'] ?? null) ? $row['CODEPOSTAL_COMMUNE'] : '';
            $location = trim(sprintf('%s %s %s, %s %s', $numero, $ruetype, $rue, $cp, $commune));
        }

        return [$summary, $location];
    }

    /**
     * Construit la description du VEVENT (préventionnistes du dossier).
     *
     * @param array<string, mixed> $row
     */
    private function buildDescription(array $row): string
    {
        $preventionnistes = \is_string($row['PREVENTIONNISTES'] ?? null) ? trim($row['PREVENTIONNISTES']) : '';

        if ('' === $preventionnistes) {
            return '';
        }

        return sprintf("Préventionniste(s) du dossier :\r\n- %s\r\n", $preventionnistes);
    }

    /**
     * Ajoute le composant VTIMEZONE au calendrier.
     * Port de Api_Service_Calendar::getVTimezoneComponent().
     *
     * @param string $tzid Identifiant de timezone (ex: "Europe/Paris")
     */
    private function addVTimezone(VCalendar $calendar, string $tzid): void
    {
        $vtimezone = $calendar->createComponent('VTIMEZONE');
        $vtimezone->add('TZID', $tzid);

        $daylight = $calendar->createComponent('DAYLIGHT');
        $daylight->add('DTSTART', '16010325T020000');
        $daylight->add('RRULE', 'FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3');
        $daylight->add('TZOFFSETFROM', '+0100');
        $daylight->add('TZOFFSETTO', '+0200');

        $vtimezone->add($daylight);

        $standard = $calendar->createComponent('STANDARD');
        $standard->add('DTSTART', '16011028T030000');
        $standard->add('RRULE', 'FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10');
        $standard->add('TZOFFSETFROM', '+0200');
        $standard->add('TZOFFSETTO', '+0100');

        $vtimezone->add($standard);

        $calendar->add($vtimezone);
    }
}
