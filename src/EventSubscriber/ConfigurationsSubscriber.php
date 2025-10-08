<?php

namespace App\EventSubscriber;

use App\Repository\ConfigurationsRepository;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ConfigurationsSubscriber implements EventSubscriberInterface
{
    /** @var ConfigurationsRepository */
    private $configurationsRepository;

    public function __construct(ConfigurationsRepository $configurationsRepository)
    {
        $this->configurationsRepository = $configurationsRepository;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $config = $this->configurationsRepository->getSingleConfiguration();

        if (null === $config) {
            return;
        }

        $dotenvVars = [];

        $debug = $config->getPrevariscDebugEnabled();
        if (null !== $debug) {
            $dotenvVars['PREVARISC_DEBUG_ENABLED'] = $debug;
        }

        $enforceSecurity = $config->getPrevariscEnforceSecurity();
        if (null !== $enforceSecurity) {
            $dotenvVars['PREVARISC_ENFORCE_SECURITY'] = $enforceSecurity;
        }

        $pluginIgnkey = $config->getPrevariscPluginIgnkey();
        if (null !== $pluginIgnkey) {
            $dotenvVars['PREVARISC_PLUGIN_IGNKEY'] = $pluginIgnkey;
        }

        $localSommeilTypes = $config->getPrevariscLocalSommeilTypes();
        if (null !== $localSommeilTypes) {
            $dotenvVars['PREVARISC_LOCAL_SOMMEIL_TYPES'] = $localSommeilTypes;
        }

        $commissionCommunaleType = $config->getPrevariscCommissionCommunaleType();
        if (null !== $commissionCommunaleType) {
            $dotenvVars['PREVARISC_COMMISSION_COMMUNALE_TYPE'] = $commissionCommunaleType->getId();
        }

        $risquesNaturelsExemples = $config->getPrevariscRisquesNaturelsExemples();
        if (null !== $risquesNaturelsExemples) {
            $dotenvVars['PREVARISC_RISQUES_NATURELS_EXEMPLES'] = $risquesNaturelsExemples;
        }

        $risquesTechnologiquesExemples = $config->getPrevariscRisquesTechnologiquesExemples();
        if (null !== $risquesTechnologiquesExemples) {
            $dotenvVars['PREVARISC_RISQUES_TECHNOLOGIQUES_EXEMPLES'] = $risquesTechnologiquesExemples;
        }

        $dashboardNextCommissionsDays = $config->getPrevariscDashboardNextCommissionsDays();
        if (null !== $dashboardNextCommissionsDays) {
            $dotenvVars['PREVARISC_DASHBOARD_NEXT_COMMISSIONS_DAYS'] = $dashboardNextCommissionsDays;
        }

        $dashboardDossiersSansAvisDays = $config->getPrevariscDashboardDossiersSansAvisDays();
        if (null !== $dashboardDossiersSansAvisDays) {
            $dotenvVars['PREVARISC_DASHBOARD_DOSSIERS_SANS_AVIS_DAYS'] = $dashboardDossiersSansAvisDays;
        }

        $dashboardCourrierSansReponseDays = $config->getPrevariscDashboardCourrierSansReponseDays();
        if (null !== $dashboardCourrierSansReponseDays) {
            $dotenvVars['PREVARISC_DASHBOARD_COURRIER_SANS_REPONSE_DAYS'] = $dashboardCourrierSansReponseDays;
        }

        $dateCommissionRelancePeriodicite = $config->getPrevariscDateCommissionRelancePeriodicite();
        if (null !== $dateCommissionRelancePeriodicite) {
            $dotenvVars['PREVARISC_DATE_COMMISSION_RELANCE_PERIODICITE'] = $dateCommissionRelancePeriodicite;
        }

        $descriptifPersonnalise = $config->getPrevariscDescriptifPersonnalise();
        if (null !== $descriptifPersonnalise) {
            $dotenvVars['PREVARISC_DESCRIPTIF_PERSONNALISE'] = $descriptifPersonnalise;
        }

        $unitePeriodiciteAnnees = $config->getPrevariscUnitePeriodiciteAnnees();
        if (null !== $unitePeriodiciteAnnees) {
            $dotenvVars['PREVARISC_UNITE_PERIODICITE_ANNEES'] = $unitePeriodiciteAnnees;
        }

        $deactivatePlatau = $config->getPrevariscDeactivatePlatau();
        if (null !== $deactivatePlatau) {
            $dotenvVars['PREVARISC_DEACTIVATE_PLATAU'] = $deactivatePlatau;
        }

        $platauNoHealthcheck = $config->getPrevariscPlatauNoHealthcheck();
        if (null !== $platauNoHealthcheck) {
            $dotenvVars['PREVARISC_PLATAU_NO_HEALTHCHECK'] = $platauNoHealthcheck;
        }

        $dossiersMaxAAfficher = $config->getPrevariscDossiersMaxAAfficher();
        if (null !== $dossiersMaxAAfficher) {
            $dotenvVars['PREVARISC_DOSSIERS_MAX_A_AFFICHER'] = $dossiersMaxAAfficher;
        }

        if ([] === $dotenvVars) {
            return;
        }

        (new Dotenv(true))->populate($dotenvVars, true);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest',
        ];
    }
}
