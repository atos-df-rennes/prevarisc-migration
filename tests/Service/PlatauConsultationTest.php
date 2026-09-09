<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\PlatauConsultation as PlatauConsultationEntity;
use App\Enum\PlatauStatutAvisEnum;
use App\Enum\PlatauStatutPecEnum;
use App\Service\PlatauConsultation;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PlatauConsultationTest extends KernelTestCase
{
    private PlatauConsultation $service;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->service = $container->get(PlatauConsultation::class);
    }

    public function testGetTempsRestantInfoRetourneEmptyQuandConsultationNull(): void
    {
        $info = $this->service->getTempsRestantInfo(null);

        $this->assertFalse($info->hasInfo());
    }

    public function testGetTempsRestantInfoRetourneEmptyQuandDateReponseAttendueNull(): void
    {
        $consultation = new PlatauConsultationEntity();
        $consultation->setDateReponseAttendue(null);

        $info = $this->service->getTempsRestantInfo($consultation);

        $this->assertFalse($info->hasInfo());
    }

    public function testGetTempsRestantInfoRetourneEmptyQuandAvisTraite(): void
    {
        $consultation = new PlatauConsultationEntity();
        $consultation->setDateReponseAttendue(new \DateTime('+10 days'));
        $consultation->setStatutAvis(PlatauStatutAvisEnum::TRAITE);

        $info = $this->service->getTempsRestantInfo($consultation);

        $this->assertFalse($info->hasInfo());
    }

    public function testGetTempsRestantInfoRetourneEmptyQuandAvisArenvoyer(): void
    {
        $consultation = new PlatauConsultationEntity();
        $consultation->setDateReponseAttendue(new \DateTime('+10 days'));
        $consultation->setStatutAvis(PlatauStatutAvisEnum::A_RENVOYER);

        $info = $this->service->getTempsRestantInfo($consultation);

        $this->assertFalse($info->hasInfo());
    }

    public function testGetTempsRestantInfoCalculeQuandAvisNonTraite(): void
    {
        $dateAttendue = new \DateTime('+15 days');
        $consultation = new PlatauConsultationEntity();
        $consultation->setDateReponseAttendue($dateAttendue);
        $consultation->setStatutAvis(PlatauStatutAvisEnum::EN_COURS);

        $info = $this->service->getTempsRestantInfo($consultation);

        $this->assertTrue($info->hasInfo());
        $this->assertSame('15 jours', $info->getTempsRestant());
        $this->assertSame('danger', $info->getCouleur());
    }

    public function testGetPlatauDataForViewRetourneNullQuandConsultationNull(): void
    {
        $data = $this->service->getPlatauDataForView(null);

        $this->assertNull($data);
    }

    public function testGetPlatauDataForViewRetourneLesDataCompletes(): void
    {
        $dateAttendue = new \DateTime('+5 days');
        $datePec = new \DateTime('-1 day');
        $dateAvis = new \DateTime('now');

        $consultation = new PlatauConsultationEntity();
        $consultation->setIdentifiant('PLATAU-123');
        $consultation->setDateReponseAttendue($dateAttendue);
        $consultation->setStatutPriseEnCompte(PlatauStatutPecEnum::PRISE_EN_COMPTE);
        $consultation->setDatePriseEnCompte($datePec);
        $consultation->setStatutAvis(PlatauStatutAvisEnum::EN_COURS);
        $consultation->setDateAvis($dateAvis);

        $data = $this->service->getPlatauDataForView($consultation);

        $this->assertIsArray($data);
        $this->assertSame('PLATAU-123', $data['identifiant']);
        $this->assertSame('5 jours', $data['temps_restant']);
        $this->assertSame('danger', $data['couleur_temps_restant']);
        $this->assertSame('taken_into_account', $data['statut_pec']);
        $this->assertSame($datePec, $data['date_pec']);
        $this->assertSame('in_progress', $data['statut_avis']);
        $this->assertSame($dateAvis, $data['date_avis']);
    }

    public function testGetPlatauDataForViewSansTempsRestantQuandAvisTraite(): void
    {
        $consultation = new PlatauConsultationEntity();
        $consultation->setIdentifiant('PLATAU-456');
        $consultation->setDateReponseAttendue(new \DateTime('+5 days'));
        $consultation->setStatutAvis(PlatauStatutAvisEnum::TRAITE);

        $data = $this->service->getPlatauDataForView($consultation);

        $this->assertIsArray($data);
        $this->assertNull($data['temps_restant']);
        $this->assertNull($data['couleur_temps_restant']);
    }
}
