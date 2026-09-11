<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Constant\TypePrescription;
use App\Entity\Dossier;
use App\Entity\PrescriptionDossier;
use App\Entity\PrescriptionType;
use App\Repository\PrescriptionDossierRepository;
use App\Service\DossierPrescriptionService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class DossierPrescriptionServiceTest extends TestCase
{
    /** @var MockObject&PrescriptionDossierRepository */
    private MockObject $repository;

    private DossierPrescriptionService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PrescriptionDossierRepository::class);
        $this->service = new DossierPrescriptionService($this->repository, $this->createStub(EntityManagerInterface::class), $this->createStub(LoggerInterface::class), $this->createStub(Security::class));
    }

    public function testReordonnerAppelleRenumerotationDeuxFois(): void
    {
        $dossier = $this->createStub(Dossier::class);
        $idsRegl = [3, 1, 2];
        $idsAutres = [5, 4];

        $callCount = 0;
        $this->repository->expects($this->exactly(2))
            ->method('renumeroterParIds')
            ->willReturnCallback(function ($d, array $ids) use ($dossier, $idsRegl, $idsAutres, &$callCount): null {
                ++$callCount;
                if (1 === $callCount) {
                    $this->assertSame($dossier, $d);
                    $this->assertSame($idsRegl, $ids);
                } else {
                    $this->assertSame($dossier, $d);
                    $this->assertSame($idsAutres, $ids);
                }

                return null;
            });

        $this->service->reordonner($dossier, $idsRegl, $idsAutres);
    }

    public function testReordonnerTransmetToujursLeMemeDossierAuRepository(): void
    {
        $dossier = $this->createStub(Dossier::class);

        $this->repository->expects($this->exactly(2))
            ->method('renumeroterParIds')
            ->with($this->identicalTo($dossier));

        $this->service->reordonner($dossier, [1, 2], [3, 4]);
    }

    public function testReordonnerAvecListesVides(): void
    {
        $dossier = $this->createStub(Dossier::class);

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
        $dossier = $this->createStub(Dossier::class);
        $idsRegl = [10, 20, 30];
        $idsAutres = [40, 50];

        $callArgs = [];
        $this->repository
            ->expects($this->exactly(2))
            ->method('renumeroterParIds')
            ->willReturnCallback(function (Dossier $d, array $ids) use (&$callArgs): void {
                $callArgs[] = $ids;
            });

        $this->service->reordonner($dossier, $idsRegl, $idsAutres);

        $this->assertSame($idsRegl, $callArgs[0]);
        $this->assertSame($idsAutres, $callArgs[1]);
    }

    public function testAjouterDepuisTypeCreePrescriptionAvecBonType(): void
    {
        $dossier = $this->createStub(Dossier::class);
        $prescriptionType = $this->createStub(PrescriptionType::class);
        $em = $this->createMock(EntityManager::class);
        $logger = $this->createStub(LoggerInterface::class);
        $security = $this->createStub(Security::class);
        $service = new DossierPrescriptionService($this->repository, $em, $logger, $security);

        $this->repository->expects($this->once())
            ->method('findMaxNumeroByDossierAndTypes')
            ->with($this->identicalTo($dossier), [TypePrescription::EXPLOITATION, TypePrescription::AMELIORATION])
            ->willReturn(0);
        $this->repository->expects($this->exactly(2))
            ->method('findByDossierAndType')
            ->willReturnCallback(function (Dossier $currentDossier, int $type) use ($dossier): array {
                $this->assertSame($dossier, $currentDossier);
                $this->assertContains($type, [TypePrescription::EXPLOITATION, TypePrescription::AMELIORATION]);

                return [];
            });
        $this->repository->expects($this->once())
            ->method('renumeroterParIds')
            ->with($this->identicalTo($dossier), []);

        $em->method('wrapInTransaction')->willReturnCallback(fn (callable $callback) => $callback($em));
        $em->expects($this->once())->method('persist')
            ->with($this->isInstanceOf(PrescriptionDossier::class));
        $em->expects($this->once())->method('flush');

        $prescription = $service->ajouterDepuisType($dossier, $prescriptionType, TypePrescription::EXPLOITATION);

        $this->assertSame(TypePrescription::EXPLOITATION, $prescription->getType());
        $this->assertSame($dossier, $prescription->getDossier());
        $this->assertSame($prescriptionType, $prescription->getPrescriptionType());
    }

    public function testAjouterDepuisTypeAssigneProchainNumero(): void
    {
        $dossier = $this->createStub(Dossier::class);
        $prescriptionType = $this->createStub(PrescriptionType::class);
        $em = $this->createStub(EntityManager::class);
        $logger = $this->createStub(LoggerInterface::class);
        $security = $this->createStub(Security::class);
        $service = new DossierPrescriptionService($this->repository, $em, $logger, $security);

        $this->repository->expects($this->once())
            ->method('findMaxNumeroByDossierAndTypes')
            ->with($this->identicalTo($dossier), [TypePrescription::REGLEMENTAIRE])
            ->willReturn(3);
        $this->repository->expects($this->once())
            ->method('findByDossierAndType')
            ->with($this->identicalTo($dossier), TypePrescription::REGLEMENTAIRE)
            ->willReturn([]);
        $this->repository->expects($this->once())
            ->method('renumeroterParIds')
            ->with($this->identicalTo($dossier), []);

        $em->method('wrapInTransaction')->willReturnCallback(fn (callable $callback) => $callback($em));

        $prescription = $service->ajouterDepuisType($dossier, $prescriptionType, TypePrescription::REGLEMENTAIRE);

        $this->assertSame(4, $prescription->getNumero());
    }

    public function testAjouterDepuisTypeGardeLienTypeEtNeCopiesPasLibelle(): void
    {
        $dossier = $this->createStub(Dossier::class);
        $prescriptionType = $this->createStub(PrescriptionType::class);
        $em = $this->createStub(EntityManager::class);
        $logger = $this->createStub(LoggerInterface::class);
        $security = $this->createStub(Security::class);
        $service = new DossierPrescriptionService($this->repository, $em, $logger, $security);

        $this->repository->expects($this->once())
            ->method('findMaxNumeroByDossierAndTypes')
            ->with($this->identicalTo($dossier), [TypePrescription::EXPLOITATION, TypePrescription::AMELIORATION])
            ->willReturn(0);
        $this->repository->expects($this->exactly(2))
            ->method('findByDossierAndType')
            ->willReturnCallback(function (Dossier $currentDossier, int $type) use ($dossier): array {
                $this->assertSame($dossier, $currentDossier);
                $this->assertContains($type, [TypePrescription::EXPLOITATION, TypePrescription::AMELIORATION]);

                return [];
            });
        $this->repository->expects($this->once())
            ->method('renumeroterParIds')
            ->with($this->identicalTo($dossier), []);

        $em->method('wrapInTransaction')->willReturnCallback(fn (callable $callback) => $callback($em));

        $prescription = $service->ajouterDepuisType($dossier, $prescriptionType, TypePrescription::AMELIORATION);

        $this->assertSame($prescriptionType, $prescription->getPrescriptionType());
        $this->assertNull($prescription->getLibelle());
    }
}
