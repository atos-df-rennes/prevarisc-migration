<?php

declare(strict_types=1);

namespace App\Tests\Service\Commission;

use App\Repository\DateCommissionRepository;
use App\Service\Commission\IcalendarSyncService;
use App\Service\Commission\IcsVTimezoneBuilder;
use PHPUnit\Framework\TestCase;

final class IcalendarSyncServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $repo = $this->createMock(DateCommissionRepository::class);
    }

    /**
     * @dataProvider provideGenerateFeedData
     *
     * @param array<string, mixed> $rows
     */
    public function testGenerateFeedContainsExpectedDescriptionSections(
        array $rows,
        string $expectedFragment
    ): void {
        $repo = $this->createMock(DateCommissionRepository::class);
        $repo->method('findForWebcalSync')->willReturn($rows);

        $service = new IcalendarSyncService($repo, new IcsVTimezoneBuilder());
        // Unfold iCal line folding (RFC 5545 §3.1) before asserting
        $ics = preg_replace('/\r\n[ \t]/', '', $service->generateFeed(null, null)) ?? '';

        $this->assertStringContainsString($expectedFragment, $ics);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function provideGenerateFeedData(): array
    {
        $baseRow = [
            'ID_DATECOMMISSION' => '1',
            'DATE_COMMISSION' => '2025-06-15',
            'HEUREDEB_COMMISSION' => '09:00:00',
            'HEUREFIN_COMMISSION' => '11:00:00',
            'LIBELLE_DATECOMMISSION' => 'Séance du 15 juin',
            'ID_COMMISSIONTYPEEVENEMENT' => '2',
            'LIBELLE_COMMISSION' => 'Commission centrale',
            'ID_DOSSIER' => '42',
            'OBJET_DOSSIER' => 'Travaux extension',
            'TYPE_DOSSIER' => '2',
            'TYPESERVINSTRUC_DOSSIER' => null,
            'SERVICEINSTRUC_DOSSIER' => null,
            'HEURE_DEB_AFFECT' => null,
            'HEURE_FIN_AFFECT' => null,
            'LIBELLE_DOSSIERTYPE' => 'Visite',
            'LIBELLE_DOSSIERNATURE' => 'Périodique',
            'ID_ETABLISSEMENT' => '10',
            'NUMEROID_ETABLISSEMENT' => 'ERP-001',
            'ID_DOSSIER_DONNANT_AVIS' => '5',
            'LIBELLE_ETABLISSEMENTINFORMATIONS' => 'Hôtel du Centre',
            'ID_GENRE' => '1',
            'NUMERO_ADRESSE' => '1',
            'LIBELLE_RUE' => 'rue de la Paix',
            'LIBELLE_RUETYPE' => 'Rue',
            'LIBELLE_COMMUNE' => 'Paris',
            'CODEPOSTAL_COMMUNE' => '75001',
            'PREVENTIONNISTES' => 'Dupont Jean',
            'CONTACTS_DOSSIER' => '- Exploitant : Martin Paul',
            'CONTACTS_ETABLISSEMENT' => null,
            'MAIRE_CONTACT' => '- Maire : Durand André, 1 place de la mairie, 75001 Paris',
            'SERVICE_INSTRUCTEUR_CONTACT' => null,
            'DERNIERE_VISITE' => '2024-03-10',
            'AVIS_DOSSIER_COMMISSION_VAL' => '1',
        ];

        return [
            'description avec contacts et avis favorable' => [
                [$baseRow],
                'Contacts du dossier :',
            ],
            'description contient le contact dossier' => [
                [$baseRow],
                'Martin Paul',
            ],
            'description contient la mairie' => [
                [$baseRow],
                'Coordonnées de la mairie :',
            ],
            'description contient la dernière visite' => [
                [$baseRow],
                'Date de la dernière visite périodique : 10/03/2024',
            ],
            'description contient avis favorable' => [
                [$baseRow],
                "Avis d'exploitation de l'établissement : Favorable",
            ],
            'description contient le préventionniste' => [
                [$baseRow],
                'Préventionniste(s) du dossier :',
            ],
            'avis défavorable' => [
                [array_merge($baseRow, ['AVIS_DOSSIER_COMMISSION_VAL' => '2'])],
                'Défavorable',
            ],
            'avis indisponible si null' => [
                [array_merge($baseRow, ['AVIS_DOSSIER_COMMISSION_VAL' => null])],
                "Avis d'exploitation indisponible",
            ],
            'aucun contact si tous null' => [
                [array_merge($baseRow, ['CONTACTS_DOSSIER' => null, 'CONTACTS_ETABLISSEMENT' => null])],
                'Aucun contact',
            ],
            'aucune mairie si null' => [
                [array_merge($baseRow, ['MAIRE_CONTACT' => null])],
                'Aucune coordonées pour la mairie.',
            ],
            'dossier sans preventionniste' => [
                [array_merge($baseRow, ['PREVENTIONNISTES' => null])],
                'Informations du prévisionniste incomplètes ou absentes',
            ],
            'avis favorable à l exploitation pour cellule' => [
                [array_merge($baseRow, ['ID_GENRE' => '3', 'AVIS_DOSSIER_COMMISSION_VAL' => '1'])],
                "Favorable à l'exploitation",
            ],
        ];
    }

    public function testGenerateFeedReturnValidIcsFormat(): void
    {
        $row = [
            'ID_DATECOMMISSION' => '1',
            'DATE_COMMISSION' => '2025-06-15',
            'HEUREDEB_COMMISSION' => '09:00:00',
            'HEUREFIN_COMMISSION' => '11:00:00',
            'LIBELLE_DATECOMMISSION' => 'Séance',
            'ID_COMMISSIONTYPEEVENEMENT' => '2',
            'LIBELLE_COMMISSION' => 'Commission A',
            'ID_DOSSIER' => '1',
            'OBJET_DOSSIER' => 'Objet',
            'TYPE_DOSSIER' => '2',
            'TYPESERVINSTRUC_DOSSIER' => null,
            'SERVICEINSTRUC_DOSSIER' => null,
            'HEURE_DEB_AFFECT' => null,
            'HEURE_FIN_AFFECT' => null,
            'LIBELLE_DOSSIERTYPE' => 'Visite',
            'LIBELLE_DOSSIERNATURE' => 'Périodique',
            'ID_ETABLISSEMENT' => '1',
            'NUMEROID_ETABLISSEMENT' => 'ERP-001',
            'ID_DOSSIER_DONNANT_AVIS' => null,
            'LIBELLE_ETABLISSEMENTINFORMATIONS' => 'Etablissement A',
            'ID_GENRE' => '1',
            'NUMERO_ADRESSE' => null,
            'LIBELLE_RUE' => null,
            'LIBELLE_RUETYPE' => null,
            'LIBELLE_COMMUNE' => null,
            'CODEPOSTAL_COMMUNE' => null,
            'PREVENTIONNISTES' => null,
            'CONTACTS_DOSSIER' => null,
            'CONTACTS_ETABLISSEMENT' => null,
            'MAIRE_CONTACT' => null,
            'SERVICE_INSTRUCTEUR_CONTACT' => null,
            'DERNIERE_VISITE' => null,
            'AVIS_DOSSIER_COMMISSION_VAL' => null,
        ];

        $repo = $this->createMock(DateCommissionRepository::class);
        $repo->method('findForWebcalSync')->willReturn([$row]);

        $service = new IcalendarSyncService($repo, new IcsVTimezoneBuilder());
        $ics = $service->generateFeed(null, null);

        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('BEGIN:VEVENT', $ics);
        $this->assertStringContainsString('END:VEVENT', $ics);
        $this->assertStringContainsString('END:VCALENDAR', $ics);
        $this->assertStringContainsString('UID:prevarisc-commission-1-dossier-1@prevarisc.local', $ics);
    }

    public function testGenerateFeedIncludesServiceInstructeurForTypeDossierInstruction(): void
    {
        $row = [
            'ID_DATECOMMISSION' => '2',
            'DATE_COMMISSION' => '2025-07-01',
            'HEUREDEB_COMMISSION' => '10:00:00',
            'HEUREFIN_COMMISSION' => '12:00:00',
            'LIBELLE_DATECOMMISSION' => 'Commission instruction',
            'ID_COMMISSIONTYPEEVENEMENT' => '1',
            'LIBELLE_COMMISSION' => 'Commission ERP',
            'ID_DOSSIER' => '99',
            'OBJET_DOSSIER' => 'Construction neuve',
            'TYPE_DOSSIER' => '1', // instruction dossier
            'TYPESERVINSTRUC_DOSSIER' => 'servInstGrp',
            'SERVICEINSTRUC_DOSSIER' => 'Groupement A',
            'HEURE_DEB_AFFECT' => null,
            'HEURE_FIN_AFFECT' => null,
            'LIBELLE_DOSSIERTYPE' => 'Instruction',
            'LIBELLE_DOSSIERNATURE' => 'Permis',
            'ID_ETABLISSEMENT' => '20',
            'NUMEROID_ETABLISSEMENT' => 'ERP-002',
            'ID_DOSSIER_DONNANT_AVIS' => null,
            'LIBELLE_ETABLISSEMENTINFORMATIONS' => 'Immeuble B',
            'ID_GENRE' => '2',
            'NUMERO_ADRESSE' => '5',
            'LIBELLE_RUE' => 'avenue de la Liberté',
            'LIBELLE_RUETYPE' => 'Avenue',
            'LIBELLE_COMMUNE' => 'Lyon',
            'CODEPOSTAL_COMMUNE' => '69001',
            'PREVENTIONNISTES' => 'Bernard Claire',
            'CONTACTS_DOSSIER' => null,
            'CONTACTS_ETABLISSEMENT' => null,
            'MAIRE_CONTACT' => '- Maire : Lefebvre Hugo',
            'SERVICE_INSTRUCTEUR_CONTACT' => '- Service Urbanisme : Dupuis Anne',
            'DERNIERE_VISITE' => null,
            'AVIS_DOSSIER_COMMISSION_VAL' => null,
        ];

        $repo = $this->createMock(DateCommissionRepository::class);
        $repo->method('findForWebcalSync')->willReturn([$row]);

        $service = new IcalendarSyncService($repo, new IcsVTimezoneBuilder());
        $ics = $service->generateFeed(null, null);

        $this->assertStringContainsString('Coordonnées du service instructeur :', $ics);
        $this->assertStringContainsString('Dupuis Anne', $ics);
    }
}
