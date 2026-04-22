<?php

declare(strict_types=1);

namespace App\Service\Commission;

use App\Repository\DateCommissionRepository;
use Sabre\VObject\Component\VCalendar;

/**
 * Service de génération du flux iCalendar pour la synchronisation webcal.
 *
 * Port simplifié de Api_Service_Calendar — une VEVENT par DateCommission.
 * Le flux inclut un VTIMEZONE (Europe/Paris par défaut) et toutes les DateCommissions
 * depuis 1 an avant aujourd'hui, filtrées par utilisateur et/ou commission si précisé.
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
     * Port simplifié de Api_Service_Calendar::sync() :
     * - Une VEVENT par DateCommission (pas une par dossier comme le legacy).
     * - DESCRIPTION = liste des OBJET_DOSSIER affectés séparés par des sauts de ligne.
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

        // Grouper les lignes par ID de DateCommission (plusieurs dossiers par DC possibles)
        /** @var array<int, array{row: array<string, mixed>, dossiers: string[]}> $dcMap */
        $dcMap = [];
        foreach ($rows as $row) {
            $dcId = isset($row['ID_DATECOMMISSION']) && is_numeric($row['ID_DATECOMMISSION'])
                ? (int) $row['ID_DATECOMMISSION']
                : 0;
            if (!isset($dcMap[$dcId])) {
                $dcMap[$dcId] = [
                    'row' => $row,
                    'dossiers' => [],
                ];
            }

            $objetDossier = isset($row['OBJET_DOSSIER']) && \is_string($row['OBJET_DOSSIER'])
                ? $row['OBJET_DOSSIER']
                : null;
            if (null !== $objetDossier && '' !== $objetDossier) {
                $dcMap[$dcId]['dossiers'][] = $objetDossier;
            }
        }

        $tz = new \DateTimeZone($tzid);
        $now = new \DateTime('now', $tz);

        foreach ($dcMap as $dcId => $data) {
            $row = $data['row'];
            $dossiers = $data['dossiers'];

            $libelleCommission = isset($row['LIBELLE_COMMISSION']) && \is_string($row['LIBELLE_COMMISSION'])
                ? $row['LIBELLE_COMMISSION']
                : '';
            $libelleDate = isset($row['LIBELLE_DATECOMMISSION']) && \is_string($row['LIBELLE_DATECOMMISSION'])
                ? $row['LIBELLE_DATECOMMISSION']
                : '';
            $summary = $libelleDate.('' !== $libelleCommission && '0' !== $libelleCommission ? ' - '.$libelleCommission : '');

            $dateStr = \is_string($row['DATE_COMMISSION'] ?? null) ? $row['DATE_COMMISSION'] : '';
            $heureDebStr = \is_string($row['HEUREDEB_COMMISSION'] ?? null) ? $row['HEUREDEB_COMMISSION'] : '00:00:00';
            $heureFinStr = \is_string($row['HEUREFIN_COMMISSION'] ?? null) ? $row['HEUREFIN_COMMISSION'] : '00:00:00';

            try {
                $dtStart = new \DateTime($dateStr.' '.$heureDebStr, $tz);
                $dtEnd = new \DateTime($dateStr.' '.$heureFinStr, $tz);
            } catch (\Exception $e) {
                continue;
            }

            $description = implode("\n", $dossiers);

            $calendar->add('VEVENT', [
                'SUMMARY' => $summary,
                'DTSTART' => $dtStart,
                'DTEND' => $dtEnd,
                'DESCRIPTION' => $description,
                'LOCATION' => $libelleCommission,
                'UID' => sprintf('prevarisc-commission-%d@prevarisc.local', $dcId),
                'DTSTAMP' => $now,
            ]);
        }

        return $calendar->serialize();
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
