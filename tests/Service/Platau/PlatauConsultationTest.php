<?php

declare(strict_types=1);

namespace App\Tests\Service\Platau;

use App\Entity\Avis;
use App\Entity\Dossier;
use App\Entity\PlatauConsultation as PlatauConsultationEntity;
use App\Enum\PlatauStatutAvisEnum;
use App\Enum\PlatauStatutPecEnum;
use App\Service\Platau\PlatauConsultation;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $data = $this->service->getPlatauDataForView(null, new Dossier());

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

        $data = $this->service->getPlatauDataForView($consultation, new Dossier());

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

        $data = $this->service->getPlatauDataForView($consultation, new Dossier());

        $this->assertIsArray($data);
        $this->assertNull($data['temps_restant']);
        $this->assertNull($data['couleur_temps_restant']);
    }

    #[DataProvider('statutsPecProvider')]
    public function testStatutPecAfficheSansModifierLaConsultation(?string $statut, ?bool $incomplet, ?string $expected): void
    {
        $consultation = new PlatauConsultationEntity();
        $consultation->setStatutPriseEnCompte($statut);

        $dossier = new Dossier();
        $dossier->setIncomplet($incomplet);

        $data = $this->service->getPlatauDataForView($consultation, $dossier);

        $this->assertIsArray($data);
        $this->assertSame($expected, $data['statut_pec']);
        $this->assertSame($statut, $consultation->getStatutPriseEnCompte());
    }

    public static function statutsPecProvider(): \Generator
    {
        foreach ([null, PlatauStatutPecEnum::INCONNU, PlatauStatutPecEnum::PRISE_EN_COMPTE, PlatauStatutPecEnum::A_RENVOYER, PlatauStatutPecEnum::EN_ERREUR, 'invalid_status'] as $statut) {
            foreach ([null, false, true] as $incomplet) {
                yield [$statut, $incomplet, $statut];
            }
        }

        yield 'awaiting sans complétude' => [PlatauStatutPecEnum::EN_ATTENTE, null, PlatauStatutPecEnum::EN_ATTENTE];
        yield 'awaiting complet' => [PlatauStatutPecEnum::EN_ATTENTE, false, PlatauStatutPecEnum::SAISIE_A_TRANSMETTRE];
        yield 'awaiting incomplet' => [PlatauStatutPecEnum::EN_ATTENTE, true, PlatauStatutPecEnum::SAISIE_A_TRANSMETTRE];
    }

    #[DataProvider('statutsAvisProvider')]
    public function testStatutAvisAfficheSansModifierLaConsultation(?string $statut, bool $avisRenseigne, ?string $expected): void
    {
        $consultation = new PlatauConsultationEntity();
        $consultation->setStatutAvis($statut);
        $consultation->setDateReponseAttendue(new \DateTime('+5 days'));

        $dossier = new Dossier();
        $dossier->setAvisCommission($avisRenseigne ? new Avis() : null);

        $tempsRestant = $this->service->getTempsRestantInfo($consultation);

        $data = $this->service->getPlatauDataForView($consultation, $dossier);

        $this->assertIsArray($data);
        $this->assertSame($expected, $data['statut_avis']);
        $this->assertSame($statut, $consultation->getStatutAvis());
        $this->assertSame($tempsRestant->getTempsRestant(), $data['temps_restant']);
        $this->assertSame($tempsRestant->getCouleur(), $data['couleur_temps_restant']);
    }

    public static function statutsAvisProvider(): \Generator
    {
        foreach ([PlatauStatutAvisEnum::TRAITE, PlatauStatutAvisEnum::A_RENVOYER, PlatauStatutAvisEnum::EN_ERREUR, 'invalid_status'] as $statut) {
            foreach ([false, true] as $avisRenseigne) {
                yield [$statut, $avisRenseigne, $statut];
            }
        }

        foreach ([null, PlatauStatutAvisEnum::INCONNU, PlatauStatutAvisEnum::EN_COURS] as $statut) {
            yield [$statut, false, $statut];
            yield [$statut, true, PlatauStatutAvisEnum::SAISI_A_TRANSMETTRE];
        }
    }

    public function testRecalculeLesStatutsApresModificationDuDossierEtDeLaPasserelle(): void
    {
        $consultation = new PlatauConsultationEntity();
        $consultation->setStatutPriseEnCompte(PlatauStatutPecEnum::EN_ATTENTE);
        $consultation->setStatutAvis(PlatauStatutAvisEnum::EN_COURS);

        $dossier = new Dossier();
        $dossier->setIncomplet(false);
        $dossier->setAvisCommission(new Avis());

        $data = $this->service->getPlatauDataForView($consultation, $dossier);
        $this->assertIsArray($data);
        $this->assertSame(PlatauStatutPecEnum::SAISIE_A_TRANSMETTRE, $data['statut_pec']);
        $this->assertSame(PlatauStatutAvisEnum::SAISI_A_TRANSMETTRE, $data['statut_avis']);

        $dossier->setIncomplet(null);
        $dossier->setAvisCommission(null);

        $data = $this->service->getPlatauDataForView($consultation, $dossier);
        $this->assertIsArray($data);
        $this->assertSame(PlatauStatutPecEnum::EN_ATTENTE, $data['statut_pec']);
        $this->assertSame(PlatauStatutAvisEnum::EN_COURS, $data['statut_avis']);

        $dossier->setIncomplet(true);
        $dossier->setAvisCommission(new Avis());

        $consultation->setStatutPriseEnCompte(PlatauStatutPecEnum::PRISE_EN_COMPTE);
        $consultation->setStatutAvis(PlatauStatutAvisEnum::TRAITE);

        $data = $this->service->getPlatauDataForView($consultation, $dossier);
        $this->assertIsArray($data);
        $this->assertSame(PlatauStatutPecEnum::PRISE_EN_COMPTE, $data['statut_pec']);
        $this->assertSame(PlatauStatutAvisEnum::TRAITE, $data['statut_avis']);

        $consultation->setStatutPriseEnCompte(PlatauStatutPecEnum::EN_ATTENTE);
        $data = $this->service->getPlatauDataForView($consultation, $dossier);
        $this->assertIsArray($data);
        $this->assertSame(PlatauStatutPecEnum::SAISIE_A_TRANSMETTRE, $data['statut_pec']);
        $this->assertSame(PlatauStatutAvisEnum::TRAITE, $data['statut_avis']);
    }
}
