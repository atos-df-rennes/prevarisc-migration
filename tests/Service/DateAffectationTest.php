<?php

namespace App\Tests\Service;

use App\Entity\CommissionTypeEvenement;
use App\Entity\DateCommission;
use App\Entity\Dossier;
use App\Entity\DossierType;
use App\Service\DateAffectation;
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
