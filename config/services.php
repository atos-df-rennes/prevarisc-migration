<?php

declare(strict_types=1);

use App\Controller\Dossier\DossierController;
use App\Controller\Etablissement\EtablissementController;
use App\Controller\IndexController;
use App\Controller\SearchController;
use App\DependencyInjection\PrevariscMailerDsnProcessor;
use App\DependencyInjection\ThirdpartyPluginEnvVarProcessor;
use App\Form\CommissionRegleType;
use App\Form\DescriptifOriginalType;
use App\Form\UtilisateurType;
use App\Security\CasLogoutSuccessHandler;
use App\Security\FormLoginAuthenticator;
use App\Security\LogoutHandler;
use App\Security\Md5SaltedEncoder;
use App\Service\Authentification;
use App\Service\CouchesCarto;
use App\Service\Dashboard\DashboardBlocConfig;
use App\Service\DataStore\DataStoreFactory;
use App\Service\DossierRapportOdtService;
use App\Service\EtablissementPieceJointeService;
use App\Service\IgnAutoconfiguration;
use App\Service\Mail\MailerConfiguration;
use App\Service\PlatauHealthcheckService;
use App\Service\ProxyConfigurationService;
use App\Validator\PasswordValidator;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Ldap\Ldap;

use function Symfony\Component\DependencyInjection\Loader\Configurator\expr;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(__DIR__.'/prevarisc.php');
    $containerConfigurator->import(__DIR__.'/platau.php');

    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->bind('$srcDir', '%kernel.project_dir%')
        ->bind('$dataPath', '%app.data_path%')
        ->bind('$realDataPath', '%app.files_path%')
        ->bind('$fileFormat', '%app.datastore_file_format%')
        ->bind('$documentsDataPath', '%app.data_path%/uploads/documents/')
        ->bind('$documentsRealDataPath', '%app.files_path%/uploads/documents/')
        ->bind('$mailEnabled', '%app.mail_enabled%')
        ->bind('$documentsCommissionDataPath', '%app.data_path%/uploads/documents_commission/')
        ->bind('$documentsCommissionRealDataPath', '%app.files_path%/uploads/documents_commission/')
        ->bind('$courriersMembresDataPath', '%app.data_path%/uploads/courriers/')
        ->bind('$courriersMembresRealDataPath', '%app.files_path%/uploads/courriers/')
        ->bind('$debugEnabled', '%app.debug%')
        ->bind('$sdis29CustomEnabled', '%app.sdis29_custom_enabled%')
        ->bind('$geobretagneUrl', '%app.geobretagne_url%')
        ->bind('$facteurCriticiteCustomEnabled', '%app.facteur_criticite_custom_enabled%')
        ->bind(FlashBagInterface::class, service('session.flash_bag'))
        ->bind('$searchCache', service('cache.search'))
    ;

    $services->load('App\\', __DIR__.'/../src/')
        ->exclude([
            __DIR__.'/../src/DependencyInjection/',
            __DIR__.'/../src/Entity/',
            __DIR__.'/../src/Enum/',
            __DIR__.'/../src/Kernel.php',
        ]);

    $services->load('App\Controller\\', __DIR__.'/../src/Controller/')
        ->tag('controller.service_arguments');

    // Environment Variable Processor pour construire MAILER_DSN depuis PREVARISC_MAIL_*
    $services->set(PrevariscMailerDsnProcessor::class)
        ->tag('container.env_var_processor');

    // Environment Variable Processor pour détecter si Plugin_SDIS29 est actif (PREVARISC_THIRDPARTY_PLUGINS)
    $services->set(ThirdpartyPluginEnvVarProcessor::class)
        ->tag('container.env_var_processor');

    $services->set(EtablissementController::class)
        ->arg('$nbDossiersAAfficher', '%app.nb_dossiers_a_afficher%')
        ->arg('$descriptifPersonnalise', '%app.descriptif_personnalise%');

    $services->set(IgnAutoconfiguration::class)
        ->arg('$autoconfPath', '%app.ign_autoconf_path%');

    $services->set(UtilisateurType::class)
        ->arg('$enforceSecurity', '%app.enforce_security%');
    $services->set(CommissionRegleType::class)
        ->arg('$commissionCommunaleType', '%app.commission_communale_type%');
    $services->set(DescriptifOriginalType::class)
        ->arg('$risquesNaturelsExemples', '%app.risques_naturels_exemples%')
        ->arg('$risquesTechnologiquesExemples', '%app.risques_technologiques_exemples%');

    $services->set(PasswordValidator::class)
        ->arg('$enforceSecurity', '%app.enforce_security%');

    $services->set(Authentification::class)
        ->arg('$casEnabled', '%app.cas_enabled%')
        ->arg('$ntlmEnabled', '%app.ntlm_enabled%')
        ->arg('$ldapEnabled', '%app.ldap_enabled%');

    // @todo: à retirer une fois les mots de passe réhashés
    $services->set(Md5SaltedEncoder::class)
        ->arg('$globalSalt', '%app.security_salt%');

    // Service LDAP partagé : connexion lazy, évite une nouvelle instance à chaque tentative d'auth.
    // Utilisé uniquement si PREVARISC_LDAP_ENABLED=1.
    $services->set('app.ldap', Ldap::class)
        ->factory([Ldap::class, 'create'])
        ->args([
            'ext_ldap',
            ['connection_string' => 'ldap://%app.ldap_host%:%app.ldap_port%'],
        ]);

    $services->set(LogoutHandler::class)
        ->arg('$casEnabled', '%app.cas_enabled%');

    $services->set(CasLogoutSuccessHandler::class)
        ->arg('$casEnabled', '%app.cas_enabled%')
        ->arg('$casHost', '%app.cas_host%')
        ->arg('$casPort', '%app.cas_port%')
        ->arg('$casPublicHost', '%app.cas_public_host%')
        ->arg('$casPublicPort', '%app.cas_public_port%')
        ->arg('$casContext', '%app.cas_context%')
        ->arg('$casNoSsl', '%app.cas_no_ssl%');

    $services->set(FormLoginAuthenticator::class)
        ->arg('$logger', service('monolog.logger.authentication'))
        ->arg('$tokenStorage', service('security.token_storage'))
        ->arg('$csrfTokenManager', service('security.csrf.token_manager'))
        ->arg('$casEnabled', '%app.cas_enabled%')
        ->arg('$casHost', '%app.cas_host%')
        ->arg('$casPort', '%app.cas_port%')
        ->arg('$casPublicHost', '%app.cas_public_host%')
        ->arg('$casPublicPort', '%app.cas_public_port%')
        ->arg('$casContext', '%app.cas_context%')
        ->arg('$casNoServerValidation', '%app.cas_no_server_validation%')
        ->arg('$casNoSsl', '%app.cas_no_ssl%')
        ->arg('$casVersion', '%app.cas_version%')
        ->arg('$ntlmEnabled', '%app.ntlm_enabled%')
        ->arg('$ldapEnabled', '%app.ldap_enabled%')
        ->arg('$ldapHost', '%app.ldap_host%')
        ->arg('$ldapPort', '%app.ldap_port%')
        ->arg('$ldapUsername', '%app.ldap_username%')
        ->arg('$ldapPassword', '%app.ldap_password%')
        ->arg('$ldapBaseDn', '%app.ldap_basedn%')
        ->arg('$ldapFilter', '%app.ldap_filter%')
        ->arg('$ldapAccountForm', '%app.ldap_account_form%')
        ->arg('$enforceSecurity', '%app.enforce_security%')
        ->arg('$ldap', service('app.ldap'));

    $services->set(DataStoreFactory::class)
        ->arg('$adapter', '%app.datastore_adapter%');

    $services->set(CouchesCarto::class)
        ->arg('$cache', service('cache.ign_capabilities'));

    $services->set(EtablissementPieceJointeService::class)
        ->arg('$datastoreLogger', service('monolog.logger.datastore'));

    $services->set('logger.null', NullLogger::class);

    $services->set(DossierRapportOdtService::class)
        ->arg('$rapportLogger', expr("parameter('app.debug') ? service('monolog.logger.rapport') : service('logger.null')"));
    $services->set(ProxyConfigurationService::class)
        ->arg('$proxyEnabled', '%app.proxy_enabled%')
        ->arg('$proxyProtocol', '%app.proxy_protocol%')
        ->arg('$proxyPort', '%app.proxy_port%')
        ->arg('$proxyHost', '%app.proxy_host%')
        ->arg('$proxyUsername', '%app.proxy_username%')
        ->arg('$proxyPassword', '%app.proxy_password%');

    $services->set(SearchController::class)
        ->arg('$deactivatePlatau', '%app.deactivate_platau%');

    $services->set(IndexController::class)
        ->arg('$deactivatePlatau', '%app.deactivate_platau%')
        ->arg('$platauNoHealthcheck', '%app.platau_no_healthcheck%');

    $services->set(DossierController::class)
        ->arg('$deactivatePlatau', '%app.deactivate_platau%');

    $services->set(DashboardBlocConfig::class)
        ->arg('$deactivatePlatau', '%app.deactivate_platau%')
        ->arg('$nextCommissionsDays', '%app.dashboard.next_commissions_days%')
        ->arg('$dossiersSansAvisDays', '%app.dashboard.dossiers_sans_avis_days%')
        ->arg('$courrierSansReponseDays', '%app.dashboard.courrier_sans_reponse_days%');

    $services->set(PlatauHealthcheckService::class)
        ->arg('$platauConfigFile', '%app.platau_config_file%')
        ->arg('$projectDir', '%kernel.project_dir%')
        ->arg('$appEnv', '%kernel.environment%');

    $services->set(MailerConfiguration::class)
        ->arg('$transport', '%app.mail_transport%')
        ->arg('$host', '%app.mail_host%')
        ->arg('$port', '%app.mail_port%')
        ->arg('$username', '%app.mail_username%')
        ->arg('$password', '%app.mail_password%')
        ->arg('$sender', '%app.mail_sender%')
        ->arg('$senderName', '%app.mail_sender_name%')
        ->arg('$enabled', '%app.mail_enabled%');
};
