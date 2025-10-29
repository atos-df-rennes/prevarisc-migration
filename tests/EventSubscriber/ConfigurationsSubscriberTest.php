<?php

namespace App\Tests\EventSubscriber;

use App\Entity\Configurations;
use App\Entity\CommissionType;
use App\Repository\ConfigurationsRepository;
use App\EventSubscriber\ConfigurationsSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ConfigurationsSubscriberTest extends TestCase
{
    private $configurationsRepository;
    private $subscriber;

    protected function setUp(): void
    {
        $this->configurationsRepository = $this->createMock(ConfigurationsRepository::class);
        $this->subscriber = new ConfigurationsSubscriber($this->configurationsRepository);
    }

    // Vérifie le comportement lorsque aucune configuration n'est disponible : les variables d'environnement ne doivent pas être définies.
    public function testRequeteKernelSansConfiguration(): void
    {
        $this->configurationsRepository
            ->expects($this->once())
            ->method('getSingleConfiguration')
            ->willReturn(null);

        $event = $this->createRequestEvent();

        $this->subscriber->onKernelRequest($event);

        $this->assertFalse(getenv('PREVARISC_DEBUG_ENABLED'));
    }

    // Vérifie que toutes les variables d'environnement sont correctement définies lorsque la configuration complète est disponible.
    public function testRequeteKernelAvecConfigurationComplete(): void
    {
        $config = new Configurations();
        $commissionType = $this->createMock(CommissionType::class);
        $commissionType->method('getId')->willReturn(5);

        $config->setPrevariscDebugEnabled(true);
        $config->setPrevariscEnforceSecurity(false);
        $config->setPrevariscPluginIgnkey('test_key_12345');
        $config->setPrevariscLocalSommeilTypes('1,2,3');
        $config->setPrevariscCommissionCommunaleType($commissionType);
        $config->setPrevariscRisquesNaturelsExemples('Inondation,Séisme');
        $config->setPrevariscRisquesTechnologiquesExemples('Industriel,Nucléaire');
        $config->setPrevariscDashboardNextCommissionsDays(30);
        $config->setPrevariscDashboardDossiersSansAvisDays(15);
        $config->setPrevariscDashboardCourrierSansReponseDays(10);
        $config->setPrevariscDateCommissionRelancePeriodicite(true);
        $config->setPrevariscDescriptifPersonnalise(true);
        $config->setPrevariscUnitePeriodiciteAnnees(false);
        $config->setPrevariscDeactivatePlatau(false);
        $config->setPrevariscPlatauNoHealthcheck(true);
        $config->setPrevariscDossiersMaxAAfficher(100);

        $this->configurationsRepository
            ->expects($this->once())
            ->method('getSingleConfiguration')
            ->willReturn($config);

        $event = $this->createRequestEvent();

        $this->subscriber->onKernelRequest($event);

        $this->assertEquals('1', getenv('PREVARISC_DEBUG_ENABLED'));
        $this->assertEquals('', getenv('PREVARISC_ENFORCE_SECURITY'));
        $this->assertEquals('test_key_12345', getenv('PREVARISC_PLUGIN_IGNKEY'));
        $this->assertEquals('1,2,3', getenv('PREVARISC_LOCAL_SOMMEIL_TYPES'));
        $this->assertEquals('5', getenv('PREVARISC_COMMISSION_COMMUNALE_TYPE'));
        $this->assertEquals('Inondation,Séisme', getenv('PREVARISC_RISQUES_NATURELS_EXEMPLES'));
        $this->assertEquals('Industriel,Nucléaire', getenv('PREVARISC_RISQUES_TECHNOLOGIQUES_EXEMPLES'));
        $this->assertEquals('30', getenv('PREVARISC_DASHBOARD_NEXT_COMMISSIONS_DAYS'));
        $this->assertEquals('15', getenv('PREVARISC_DASHBOARD_DOSSIERS_SANS_AVIS_DAYS'));
        $this->assertEquals('10', getenv('PREVARISC_DASHBOARD_COURRIER_SANS_REPONSE_DAYS'));
        $this->assertEquals('1', getenv('PREVARISC_DATE_COMMISSION_RELANCE_PERIODICITE'));
        $this->assertEquals('1', getenv('PREVARISC_DESCRIPTIF_PERSONNALISE'));
        $this->assertEquals('', getenv('PREVARISC_UNITE_PERIODICITE_ANNEES'));
        $this->assertEquals('', getenv('PREVARISC_DEACTIVATE_PLATAU'));
        $this->assertEquals('1', getenv('PREVARISC_PLATAU_NO_HEALTHCHECK'));
        $this->assertEquals('100', getenv('PREVARISC_DOSSIERS_MAX_A_AFFICHER'));
    }

    private function createRequestEvent(): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        return new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );
    }

    protected function tearDown(): void
    {
        putenv('PREVARISC_DEBUG_ENABLED');
        putenv('PREVARISC_ENFORCE_SECURITY');
        putenv('PREVARISC_PLUGIN_IGNKEY');
        putenv('PREVARISC_LOCAL_SOMMEIL_TYPES');
        putenv('PREVARISC_COMMISSION_COMMUNALE_TYPE');
        putenv('PREVARISC_RISQUES_NATURELS_EXEMPLES');
        putenv('PREVARISC_RISQUES_TECHNOLOGIQUES_EXEMPLES');
        putenv('PREVARISC_DASHBOARD_NEXT_COMMISSIONS_DAYS');
        putenv('PREVARISC_DASHBOARD_DOSSIERS_SANS_AVIS_DAYS');
        putenv('PREVARISC_DASHBOARD_COURRIER_SANS_REPONSE_DAYS');
        putenv('PREVARISC_DATE_COMMISSION_RELANCE_PERIODICITE');
        putenv('PREVARISC_DESCRIPTIF_PERSONNALISE');
        putenv('PREVARISC_UNITE_PERIODICITE_ANNEES');
        putenv('PREVARISC_DEACTIVATE_PLATAU');
        putenv('PREVARISC_PLATAU_NO_HEALTHCHECK');
        putenv('PREVARISC_DOSSIERS_MAX_A_AFFICHER');
    }
}
