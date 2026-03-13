<?php

namespace App\Tests\Service;

use App\Entity\Dossier;
use App\Repository\PrescriptionDossierRepository;
use App\Service\DossierPrescriptionService;
use PHPUnit\Framework\TestCase;

final class DossierPrescriptionServiceTest extends TestCase
{
    /** @var PrescriptionDossierRepository */
    private $repository;

    /** @var DossierPrescriptionService */
    private $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PrescriptionDossierRepository::class);
        $this->service = new DossierPrescriptionService($this->repository);
    }

    public function testReordonnerAppelleRenumerotationDeuxFois(): void
    {
        $dossier = $this->createMock(Dossier::class);
        $idsRegl = [3, 1, 2];
        $idsAutres = [5, 4];

        $this->repository->expects($this->exactly(2))
            ->method('renumeroterParIds')
            ->withConsecutive(
                [$dossier, $idsRegl],
                [$dossier, $idsAutres]
            );

        $this->service->reordonner($dossier, $idsRegl, $idsAutres);
    }

    public function testReordonnerTransmetToujursLeMemeDossierAuRepository(): void
    {
        $dossier = $this->createMock(Dossier::class);

        $this->repository->expects($this->exactly(2))
            ->method('renumeroterParIds')
            ->with($this->identicalTo($dossier));

        $this->service->reordonner($dossier, [1, 2], [3, 4]);
    }

    public function testReordonnerAvecListesVides(): void
    {
        $dossier = $this->createMock(Dossier::class);

        $this->repository->expects($this->exactly(2))
            ->method('renumeroterParIds');

        $this->service->reordonner($dossier, [], []);
    }

    /**
     * Vérifie que le service ne mélange pas les deux groupes :
     * les IDs réglementaires et les autres sont passés séparément au repository.
     */
    public function testReordonnerPasseLesBonIdsAChaqueListe(): void
    {
        $dossier = $this->createMock(Dossier::class);
        $idsRegl = [10, 20, 30];
        $idsAutres = [40, 50];

        $callArgs = [];
        $this->repository
            ->method('renumeroterParIds')
            ->willReturnCallback(function (Dossier $d, array $ids) use (&$callArgs): void {
                $callArgs[] = $ids;
            });

        $this->service->reordonner($dossier, $idsRegl, $idsAutres);

        $this->assertSame($idsRegl, $callArgs[0]);
        $this->assertSame($idsAutres, $callArgs[1]);
    }
}
