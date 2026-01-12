<?php

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
    /** @var DateAffectation */
    private $dateAffectation;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        self::bootKernel();

        $container = self::$container;

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

        $this->assertSame(null, $this->dateAffectation->getIdDateCommission($dossier, $dateCommission));
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

        $this->assertSame(null, $this->dateAffectation->getIdDateCommission($dossier, $dateCommission));
    }

    public function testGetWithEtudeAndNullDateCommissionLogsError(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('error');

        $dateAffectation = new DateAffectation($logger);
        $dossier = $this->createDossierOfType(1);

        $this->assertSame(null, $dateAffectation->getIdDateCommission($dossier, null));
    }

    public function testGetWithVisiteAndNullDateCommissionLogsError(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('error');

        $dateAffectation = new DateAffectation($logger);
        $dossier = $this->createDossierOfType(2);

        $this->assertSame(null, $dateAffectation->getIdDateCommission($dossier, null));
    }

    public function testGetWithVisiteAndNullTypeEvenementLogsError(): void
    {
        // Pour une visite, si le type d'événement est null, on log une erreur
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Le type d\'événement de la date de commission est null.', [
                'id_dossier' => 1,
                'id_date_commission' => 2
            ]);

        $dateAffectation = new DateAffectation($logger);
        $dossier = $this->createDossierOfType(2);
        $dateCommission = $this->createDateCommissionOfType();

        $this->assertSame(null, $dateAffectation->getIdDateCommission($dossier, $dateCommission));
    }

    public function testGetWithNullDossierTypeLogsError(): void
    {
        // Si le type du dossier est null, on log une erreur
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Le type de dossier est null.', ['id_dossier' => 1]);

        $dateAffectation = new DateAffectation($logger);
        $dossier = $this->createConfiguredMock(Dossier::class, ['getId' => 1, 'getType' => null]);
        $dateCommission = $this->createDateCommissionOfType();

        $this->assertSame(null, $dateAffectation->getIdDateCommission($dossier, $dateCommission));
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

        $this->assertSame(null, $dateAffectation->getIdDateCommission($dossier, $dateCommission));
    }

    private function createDossierOfType(int $typeId): Dossier
    {
        return $this->createConfiguredMock(Dossier::class, ['getId' => 1, 'getType' => $this->createConfiguredMock(DossierType::class, ['getId' => $typeId])]);
    }

    private function createDateCommissionOfType(?int $typeId = null): DateCommission
    {
        $dateCommission = $this->createConfiguredMock(DateCommission::class, ['getId' => 2]);

        if (null !== $typeId) {
            $dateCommission->method('getTypeEvenement')->willReturn(
                $this->createConfiguredMock(CommissionTypeEvenement::class, ['getId' => $typeId])
            );
        }

        return $dateCommission;
    }
}
