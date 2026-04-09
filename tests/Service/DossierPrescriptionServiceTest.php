<?php

namespace App\Tests\Service;

use App\Constant\TypePrescription;
use App\Entity\Dossier;
use App\Entity\PrescriptionDossier;
use App\Entity\PrescriptionType;
use App\Repository\PrescriptionDossierRepository;
use App\Service\DossierPrescriptionService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

final class DossierPrescriptionServiceTest extends TestCase
{
    /** @var PrescriptionDossierRepository */
    private $repository;

    /** @var DossierPrescriptionService */
    private $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PrescriptionDossierRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $security = $this->createMock(Security::class);
        $this->service = new DossierPrescriptionService($this->repository, $em, $logger, $security);
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

    public function testAjouterDepuisTypeCreePrescriptionAvecBonType(): void
    {
        $dossier = $this->createMock(Dossier::class);
        $prescriptionType = $this->createMock(PrescriptionType::class);
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->createMock(LoggerInterface::class);
        $security = $this->createMock(Security::class);
        $service = new DossierPrescriptionService($this->repository, $em, $logger, $security);

        $this->repository->method('findMaxNumeroByDossierAndTypes')->willReturn(0);
        $this->repository->method('findByDossierAndType')->willReturn([]);

        $em->method('wrapInTransaction')->willReturnCallback(function (callable $callback) use ($em) {
            return $callback($em);
        });
        $em->expects($this->once())->method('persist')
            ->with($this->isInstanceOf(PrescriptionDossier::class));
        $em->expects($this->once())->method('flush');

        $prescription = $service->ajouterDepuisType($dossier, $prescriptionType, TypePrescription::EXPLOITATION);

        self::assertSame(TypePrescription::EXPLOITATION, $prescription->getType());
        self::assertSame($dossier, $prescription->getDossier());
        self::assertSame($prescriptionType, $prescription->getPrescriptionType());
    }

    public function testAjouterDepuisTypeAssigneProchainNumero(): void
    {
        $dossier = $this->createMock(Dossier::class);
        $prescriptionType = $this->createMock(PrescriptionType::class);
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->createMock(LoggerInterface::class);
        $security = $this->createMock(Security::class);
        $service = new DossierPrescriptionService($this->repository, $em, $logger, $security);

        $this->repository->method('findMaxNumeroByDossierAndTypes')->willReturn(3);
        $this->repository->method('findByDossierAndType')->willReturn([]);

        $em->method('wrapInTransaction')->willReturnCallback(function (callable $callback) use ($em) {
            return $callback($em);
        });

        $prescription = $service->ajouterDepuisType($dossier, $prescriptionType, TypePrescription::REGLEMENTAIRE);

        self::assertSame(4, $prescription->getNumero());
    }

    public function testAjouterDepuisTypeGardeLienTypeEtNeCopiesPasLibelle(): void
    {
        $dossier = $this->createMock(Dossier::class);
        $prescriptionType = $this->createMock(PrescriptionType::class);
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->createMock(LoggerInterface::class);
        $security = $this->createMock(Security::class);
        $service = new DossierPrescriptionService($this->repository, $em, $logger, $security);

        $this->repository->method('findMaxNumeroByDossierAndTypes')->willReturn(0);
        $this->repository->method('findByDossierAndType')->willReturn([]);

        $em->method('wrapInTransaction')->willReturnCallback(function (callable $callback) use ($em) {
            return $callback($em);
        });

        $prescription = $service->ajouterDepuisType($dossier, $prescriptionType, TypePrescription::AMELIORATION);

        self::assertSame($prescriptionType, $prescription->getPrescriptionType());
        self::assertNull($prescription->getLibelle());
    }
}
