<?php

declare(strict_types=1);

namespace App\Service\Commission;

use Sabre\VObject\Component\VCalendar;

/**
 * Construit le composant VTIMEZONE d'un flux iCalendar.
 *
 * Sans ce composant, un DTSTART/DTEND qualifié par TZID=Europe/Paris n'est pas
 * résolvable par certains clients (Outlook desktop notamment), qui retombent
 * alors sur un décalage horaire erroné (ex : avance/retard d'une heure).
 */
class IcsVTimezoneBuilder
{
    /**
     * Ajoute le composant VTIMEZONE au calendrier.
     *
     * @param string $tzid Identifiant de timezone (ex: "Europe/Paris")
     */
    public function addVTimezone(VCalendar $calendar, string $tzid): void
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
