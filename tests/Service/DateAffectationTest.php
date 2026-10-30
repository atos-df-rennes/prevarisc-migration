<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\CommissionTypeEvenement;
use App\Entity\DateCommission;
use App\Entity\Dossier;
use App\Entity\DossierType;
use App\Service\DateAffectation;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

// @todo: Mettre des fixtures avec une base de test au lieu des mocks.
final class DateAffectationTest extends KernelTestCase
{
    private DateAffectation $dateAffectation;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        self::bootKernel();

        $container = self::getContainer();

        $this->dateAffectation = $container->get(DateAffectation::class);
    }

    public function testGetWithEtude(): void
    {
        // Étude
        $dossier = $this->createDossierOfType(1);
        $dateCommission = $this->createDateCommissionOfType();

        $this->assertSame(2, $this->dateAffectation->getIdDateCommission($dossier, $dateCommission));
    }

    public function testGetWithVisiteCommissionSalle(): void
    {
        // Visite
        $dossier = $this->createDossierOfType(2);
        $dateCommission = $this->createDateCommissionOfType(1);

        $this->assertSame(2, $this->dateAffectation->getIdDateCommission($dossier, $dateCommission));
    }

    public function testGetWithVisiteCommissionVisite(): void
    {
        // Visite
        $dossier = $this->createDossierOfType(2);
        $dateCommission = $this->createDateCommissionOfType(2);

        $this->assertNull($this->dateAffectation->getIdDateCommission($dossier, $dateCommission));
    }

    public function testGetWithGroupeDeVisitesCommissionSalle(): void
    {
        // Groupe de visites
        $dossier = $this->createDossierOfType(3);
        $dateCommission = $this->createDateCommissionOfType(1);

        $this->assertSame(2, $this->dateAffectation->getIdDateCommission($dossier, $dateCommission));
    }

    public function testGetWithGroupeDeVisitesCommissionGroupeDeVisites(): void
    {
        // Groupe de visites
        $dossier = $this->createDossierOfType(3);
        $dateCommission = $this->createDateCommissionOfType(3);

        $this->assertNull($this->dateAffectation->getIdDateCommission($dossier, $dateCommission));
    }

    public function testGetWithEtudeAndNullDateCommissionLogsError(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('error');

        $dateAffectation = new DateAffectation($logger);
        $dossier = $this->createDossierOfType(1);

        $this->assertNull($dateAffectation->getIdDateCommission($dossier, null));
    }

    public function testGetWithVisiteAndNullDateCommissionLogsError(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('error');

        $dateAffectation = new DateAffectation($logger);
        $dossier = $this->createDossierOfType(2);

        $this->assertNull($dateAffectation->getIdDateCommission($dossier, null));
    }

    public function testGetWithVisiteAndNullTypeEvenementLogsError(): void
    {
        // Pour une visite, si le type d'événement est null, on log une erreur
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with("Le type d'événement de la date de commission est null.", [
                'id_dossier' => 1,
                'id_date_commission' => 2
            ]);

        $dateAffectation = new DateAffectation($logger);
        $dossier = $this->createDossierOfType(2);
        $dateCommission = $this->createDateCommissionOfType();

        $this->assertNull($dateAffectation->getIdDateCommission($dossier, $dateCommission));
    }

    public function testGetWithNullDossierTypeLogsError(): void
    {
        // Si le type du dossier est null, on log une erreur
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Le type de dossier est null.', ['id_dossier' => 1]);

        $dateAffectation = new DateAffectation($logger);
        $dossier = $this->createConfiguredStub(Dossier::class, ['getId' => 1, 'getType' => null]);
        $dateCommission = $this->createDateCommissionOfType();

        $this->assertNull($dateAffectation->getIdDateCommission($dossier, $dateCommission));
    }

    public function testGetWithDossierTypeNotHandled(): void
    {
        // Type de dossier non géré (par exemple type 4) : retourne null sans log d'erreur
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('error');

        $dateAffectation = new DateAffectation($logger);
        $dossier = $this->createDossierOfType(4);
        $dateCommission = $this->createDateCommissionOfType(1);

        $this->assertNull($dateAffectation->getIdDateCommission($dossier, $dateCommission));
    }

    private function createDossierOfType(int $typeId): Dossier
    {
        return $this->createConfiguredStub(Dossier::class, [
            'getId' => 1,
            'getType' => $this->createConfiguredStub(DossierType::class, ['getId' => $typeId]),
        ]);
    }

    private function createDateCommissionOfType(?int $typeId = null): DateCommission
    {
        $configuration = ['getId' => 2];

        if (null !== $typeId) {
            $configuration['getTypeEvenement'] = $this->createConfiguredStub(CommissionTypeEvenement::class, ['getId' => $typeId]);
        }

        return $this->createConfiguredStub(DateCommission::class, $configuration);
    }
}
