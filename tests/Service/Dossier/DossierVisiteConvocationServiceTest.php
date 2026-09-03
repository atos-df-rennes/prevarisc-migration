<?php

namespace App\Tests\Service\Dossier;

use App\Constant\CommissionTypeEvenement as TypeEvenement;
use App\Entity\DateCommission;
use App\Entity\Dossier;
use App\Entity\DossierAffectation;
use App\Repository\DateCommissionRepository;
use App\Repository\DossierAffectationRepository;
use App\Service\Dossier\DossierVisiteConvocationService;
use PHPUnit\Framework\TestCase;

final class DossierVisiteConvocationServiceTest extends TestCase
{
    public function testRetourneDesChampsVidesQuandLeDossierNEstPasAffecteAUneVisite(): void
    {
        $dossier = new Dossier();

        $affectationRepository = $this->createMock(DossierAffectationRepository::class);
        $affectationRepository->expects(self::once())
            ->method('findAffectationByDossierAndType')
            ->with($dossier, [TypeEvenement::TYPE_VISITE, TypeEvenement::TYPE_GROUPE_VISITE])
            ->willReturn(null)
        ;

        $dateCommissionRepository = $this->createMock(DateCommissionRepository::class);
        $dateCommissionRepository->expects(self::never())->method('findGroupeDatesLiees');

        $service = new DossierVisiteConvocationService($affectationRepository, $dateCommissionRepository);

        self::assertSame(
            ['dateVisite' => '', 'heureVisite' => ''],
            $service->getInfosConvocation($dossier)
        );
    }

    public function testConstruitLaDateEtLHeureDUneVisiteSurUneSeuleDate(): void
    {
        $dossier = new Dossier();

        $dateCommission = $this->creerDateCommission(1, '2026-09-15', '09:00', '12:00');

        $affectation = new DossierAffectation();
        $affectation->setDateCommission($dateCommission);

        $affectationRepository = $this->createMock(DossierAffectationRepository::class);
        $affectationRepository->method('findAffectationByDossierAndType')->willReturn($affectation);

        $dateCommissionRepository = $this->createMock(DateCommissionRepository::class);
        $dateCommissionRepository->expects(self::once())
            ->method('findGroupeDatesLiees')
            ->with($dateCommission)
            ->willReturn([$dateCommission])
        ;

        $service = new DossierVisiteConvocationService($affectationRepository, $dateCommissionRepository);

        self::assertSame(
            ['dateVisite' => '15 septembre 2026', 'heureVisite' => '09:00 à 12:00'],
            $service->getInfosConvocation($dossier)
        );
    }

    public function testAgregeLesDatesEtHeuresDUnGroupeDeDatesLiees(): void
    {
        $dossier = new Dossier();

        $dateCommissionAffectee = $this->creerDateCommission(2, '2026-09-16', '14:00', '18:00');

        $affectation = new DossierAffectation();
        $affectation->setDateCommission($dateCommissionAffectee);

        $groupeDates = [
            $this->creerDateCommission(1, '2026-09-15', '09:00', '12:00'),
            $dateCommissionAffectee,
        ];

        $affectationRepository = $this->createMock(DossierAffectationRepository::class);
        $affectationRepository->method('findAffectationByDossierAndType')->willReturn($affectation);

        $dateCommissionRepository = $this->createMock(DateCommissionRepository::class);
        $dateCommissionRepository->method('findGroupeDatesLiees')->with($dateCommissionAffectee)->willReturn($groupeDates);

        $service = new DossierVisiteConvocationService($affectationRepository, $dateCommissionRepository);

        self::assertSame(
            [
                'dateVisite' => '15 septembre 2026, 16 septembre 2026',
                'heureVisite' => '09:00 à 12:00, 14:00 à 18:00',
            ],
            $service->getInfosConvocation($dossier)
        );
    }

    public function testRetourneDesChampsVidesQuandLAffectationNAPasDeDateDeCommission(): void
    {
        $dossier = new Dossier();
        $affectation = new DossierAffectation();

        $affectationRepository = $this->createMock(DossierAffectationRepository::class);
        $affectationRepository->method('findAffectationByDossierAndType')->willReturn($affectation);

        $dateCommissionRepository = $this->createMock(DateCommissionRepository::class);
        $dateCommissionRepository->expects(self::never())->method('findGroupeDatesLiees');

        $service = new DossierVisiteConvocationService($affectationRepository, $dateCommissionRepository);

        self::assertSame(
            ['dateVisite' => '', 'heureVisite' => ''],
            $service->getInfosConvocation($dossier)
        );
    }

    private function creerDateCommission(int $id, string $date, string $heureDebut, string $heureFin): DateCommission
    {
        $dateCommission = new DateCommission();
        $dateCommission->setDate(new \DateTime($date));
        $dateCommission->setHeureDebut(\DateTime::createFromFormat('H:i', $heureDebut));
        $dateCommission->setHeureFin(\DateTime::createFromFormat('H:i', $heureFin));

        $reflection = new \ReflectionClass($dateCommission);
        $property = $reflection->getProperty('id');
        $property->setValue($dateCommission, $id);

        return $dateCommission;
    }
}
