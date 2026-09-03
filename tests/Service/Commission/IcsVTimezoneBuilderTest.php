<?php

declare(strict_types=1);

namespace App\Tests\Service\Commission;

use App\Service\Commission\IcsVTimezoneBuilder;
use PHPUnit\Framework\TestCase;
use Sabre\VObject\Component\VCalendar;

class IcsVTimezoneBuilderTest extends TestCase
{
    public function testAddVTimezoneDefinesEuropeParisRules(): void
    {
        $calendar = new VCalendar(['PRODID' => 'Test']);

        new IcsVTimezoneBuilder()->addVTimezone($calendar, 'Europe/Paris');

        $ics = $calendar->serialize();

        $this->assertStringContainsString('BEGIN:VTIMEZONE', $ics);
        $this->assertStringContainsString('TZID:Europe/Paris', $ics);
        $this->assertStringContainsString('BEGIN:DAYLIGHT', $ics);
        $this->assertStringContainsString('TZOFFSETFROM:+0100', $ics);
        $this->assertStringContainsString('TZOFFSETTO:+0200', $ics);
        $this->assertStringContainsString('BEGIN:STANDARD', $ics);
        $this->assertStringContainsString('TZOFFSETFROM:+0200', $ics);
        $this->assertStringContainsString('TZOFFSETTO:+0100', $ics);
        $this->assertStringContainsString('END:VTIMEZONE', $ics);
    }

    public function testDtstartWithMatchingVtimezoneStaysUnambiguous(): void
    {
        // Non-régression du bug d'export Outlook : un DTSTART qualifié par TZID
        // doit toujours être accompagné du VTIMEZONE correspondant dans le même
        // VCALENDAR, sous peine de décalage horaire à la lecture (cf. #export Outlook).
        $calendar = new VCalendar(['PRODID' => 'Test']);
        new IcsVTimezoneBuilder()->addVTimezone($calendar, 'Europe/Paris');

        $tz = new \DateTimeZone('Europe/Paris');
        $calendar->add('VEVENT', [
            'SUMMARY' => 'Test',
            'DTSTART' => new \DateTime('2026-01-15 09:00:00', $tz),
            'DTEND' => new \DateTime('2026-01-15 10:00:00', $tz),
            'UID' => 'test@prevarisc.local',
            'DTSTAMP' => new \DateTime('now', new \DateTimeZone('UTC')),
        ]);

        $ics = $calendar->serialize();

        $vtimezonePos = strpos($ics, 'BEGIN:VTIMEZONE');
        $veventPos = strpos($ics, 'BEGIN:VEVENT');

        $this->assertNotFalse($vtimezonePos);
        $this->assertNotFalse($veventPos);
        $this->assertLessThan($veventPos, $vtimezonePos, 'Le VTIMEZONE doit précéder le VEVENT qui le référence.');
        $this->assertStringContainsString('DTSTART;TZID=Europe/Paris:20260115T090000', $ics);
    }
}
