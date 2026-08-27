<?php

namespace App\Tests\Service\Dashboard;

use App\Dto\Repository\DossierAffectationDto;
use App\Dto\Repository\NextCommissionDto;
use App\Entity\Utilisateur;
use App\Repository\DateCommissionRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;
use App\Service\Dashboard\DashboardBlocConfig;
use App\Service\Dashboard\DashboardService;
use App\Service\FeedService;
use App\Service\NotificationService;
use PHPUnit\Framework\TestCase;

final class DashboardServiceTest extends TestCase
{
    /**
     * Vérifie que le bloc "prochaines commissions" ne fait qu'un seul aller-retour base de données
     * (au lieu d'un par commission) pour charger les dossiers affectés/non-affectés de l'ordre du jour.
     * Non-régression pour la correction du N+1 signalé par la revue de performance.
     */
    public function testGetNextCommissionOdjBatchesQueriesInsteadOfQueryingPerCommission(): void
    {
        $blocConfig = new DashboardBlocConfig(true, 30, 30, 30);

        $commission1 = NextCommissionDto::fromArray(['ID_DATECOMMISSION' => 1, 'LIBELLE_COMMISSION' => 'Commission A']);
        $commission2 = NextCommissionDto::fromArray(['ID_DATECOMMISSION' => 2, 'LIBELLE_COMMISSION' => 'Commission B']);

        $dossierAffect1 = DossierAffectationDto::fromArray(['ID_DOSSIER' => 10, 'ID_DATECOMMISSION_AFFECT' => 1]);
        $dossierNonAffect2 = DossierAffectationDto::fromArray(['ID_DOSSIER' => 20, 'ID_DATECOMMISSION_AFFECT' => 2]);

        $dateCommissionRepository = $this->createMock(DateCommissionRepository::class);
        $dateCommissionRepository->expects(self::once())
            ->method('findNextCommissionsForDashboard')
            ->willReturn([$commission1, $commission2])
        ;

        // Une seule requête pour les deux commissions, jamais une par commission.
        $dateCommissionRepository->expects(self::once())
            ->method('findDossierAffectForCommissions')
            ->with([1, 2])
            ->willReturn([1 => [$dossierAffect1]])
        ;
        $dateCommissionRepository->expects(self::once())
            ->method('findDossierNonAffectForCommissions')
            ->with([1, 2])
            ->willReturn([2 => [$dossierNonAffect2]])
        ;
        $dateCommissionRepository->expects(self::never())->method('findDossierAffectForCommission');
        $dateCommissionRepository->expects(self::never())->method('findDossierNonAffectForCommission');

        $dashboardService = new DashboardService(
            $blocConfig,
            $dateCommissionRepository,
            $this->createMock(EtablissementRepository::class),
            $this->createMock(DossierRepository::class),
            $this->createMock(FeedService::class),
            $this->createMock(NotificationService::class)
        );

        $result = $dashboardService->getDataForBloc('nextCommissionsOdj', new Utilisateur());

        self::assertCount(2, $result);
        self::assertSame([$dossierAffect1], $result[0]['odj']);
        self::assertSame([$dossierNonAffect2], $result[1]['odj']);
    }
}
