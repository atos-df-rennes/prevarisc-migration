<?php

namespace App\Tests\Service;

use App\Entity\PlatauConsultation;
use App\Enum\PlatauStatutAvisEnum;
use App\Service\PlatauConsultationService;
use App\Service\PlatauTempsRestant;
use PHPUnit\Framework\TestCase;

class PlatauConsultationServiceTest extends TestCase
{
    /** @var PlatauTempsRestant */
    private $platauTempsRestantService;

    /** @var PlatauConsultationService */
    private $service;

    protected function setUp(): void
    {
        $this->platauTempsRestantService = $this->createMock(PlatauTempsRestant::class);
        $this->service = new PlatauConsultationService($this->platauTempsRestantService);
    }

    public function testGetTempsRestantInfoRetourneEmptyQuandConsultationNull(): void
    {
        $info = $this->service->getTempsRestantInfo(null);

        $this->assertFalse($info->hasInfo());
    }

    public function testGetTempsRestantInfoRetourneEmptyQuandDateReponseAttendueNull(): void
    {
        $consultation = new PlatauConsultation();
        $consultation->setDateReponseAttendue(null);

        $info = $this->service->getTempsRestantInfo($consultation);

        $this->assertFalse($info->hasInfo());
    }

    public function testGetTempsRestantInfoRetourneEmptyQuandAvisTraite(): void
    {
        $consultation = new PlatauConsultation();
        $consultation->setDateReponseAttendue(new \DateTime('+10 days'));
        $consultation->setStatutAvis(PlatauStatutAvisEnum::TRAITE);

        $info = $this->service->getTempsRestantInfo($consultation);

        $this->assertFalse($info->hasInfo());
    }

    public function testGetTempsRestantInfoRetourneEmptyQuandAvisArenvoyer(): void
    {
        $consultation = new PlatauConsultation();
        $consultation->setDateReponseAttendue(new \DateTime('+10 days'));
        $consultation->setStatutAvis(PlatauStatutAvisEnum::A_RENVOYER);

        $info = $this->service->getTempsRestantInfo($consultation);

        $this->assertFalse($info->hasInfo());
    }

    public function testGetTempsRestantInfoCalculeQuandAvisNonTraite(): void
    {
        $dateAttendue = new \DateTime('+15 days');
        $consultation = new PlatauConsultation();
        $consultation->setDateReponseAttendue($dateAttendue);
        $consultation->setStatutAvis(PlatauStatutAvisEnum::EN_COURS);

        $this->platauTempsRestantService
            ->expects($this->once())
            ->method('calculate')
            ->with($dateAttendue)
            ->willReturn('15 jours');

        $this->platauTempsRestantService
            ->expects($this->once())
            ->method('getCouleurTempsRestant')
            ->with($dateAttendue)
            ->willReturn('success');

        $info = $this->service->getTempsRestantInfo($consultation);

        $this->assertTrue($info->hasInfo());
        $this->assertSame('15 jours', $info->getTempsRestant());
        $this->assertSame('success', $info->getCouleur());
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

        $consultation = new PlatauConsultation();
        $consultation->setIdentifiant('PLATAU-123');
        $consultation->setDateReponseAttendue($dateAttendue);
        $consultation->setStatutPriseEnCompte('taken_into_account');
        $consultation->setDatePriseEnCompte($datePec);
        $consultation->setStatutAvis(PlatauStatutAvisEnum::EN_COURS);
        $consultation->setDateAvis($dateAvis);

        $this->platauTempsRestantService
            ->method('calculate')
            ->willReturn('5 jours');

        $this->platauTempsRestantService
            ->method('getCouleurTempsRestant')
            ->willReturn('warning');

        $data = $this->service->getPlatauDataForView($consultation);

        $this->assertIsArray($data);
        $this->assertSame('PLATAU-123', $data['identifiant']);
        $this->assertSame('5 jours', $data['temps_restant']);
        $this->assertSame('warning', $data['couleur_temps_restant']);
        $this->assertSame('taken_into_account', $data['statut_pec']);
        $this->assertSame($datePec, $data['date_pec']);
        $this->assertSame(PlatauStatutAvisEnum::EN_COURS, $data['statut_avis']);
        $this->assertSame($dateAvis, $data['date_avis']);
    }

    public function testGetPlatauDataForViewSansTempsRestantQuandAvisTraite(): void
    {
        $consultation = new PlatauConsultation();
        $consultation->setIdentifiant('PLATAU-456');
        $consultation->setDateReponseAttendue(new \DateTime('+5 days'));
        $consultation->setStatutAvis(PlatauStatutAvisEnum::TRAITE);

        $data = $this->service->getPlatauDataForView($consultation);

        $this->assertIsArray($data);
        $this->assertNull($data['temps_restant']);
        $this->assertNull($data['couleur_temps_restant']);
    }
}
