<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Ldap\Ldap;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(__DIR__ . '/prevarisc.php');
    $containerConfigurator->import(__DIR__ . '/platau.php');

    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->bind('$srcDir', '%kernel.project_dir%')
        ->bind('$dataPath', '%app.data_path%')
        ->bind('$realDataPath', '%app.files_path%')
        ->bind('$documentsDataPath', '%app.data_path%/uploads/documents/')
        ->bind('$documentsRealDataPath', '%app.files_path%/uploads/documents/')
        ->bind('$mailEnabled', '%app.mail_enabled%')
        ->bind('$documentsCommissionDataPath', '%app.data_path%/uploads/documents_commission/')
        ->bind('$documentsCommissionRealDataPath', '%app.files_path%/uploads/documents_commission/')
        ->bind('$courriersMembresDataPath', '%app.data_path%/uploads/courriers/')
        ->bind('$courriersMembresRealDataPath', '%app.files_path%/uploads/courriers/')
        ->bind('$debugEnabled', '%app.debug%')
        ->bind(FlashBagInterface::class, ref('session.flash_bag'))
    ;


    $services->load('App\\', __DIR__ . '/../src/')
        ->exclude([
        __DIR__ . '/../src/DependencyInjection/',
        __DIR__ . '/../src/Entity/',
        __DIR__ . '/../src/Enum/',
        __DIR__ . '/../src/Kernel.php',
    ]);

    $services->load('App\Controller\\', __DIR__ . '/../src/Controller/')
        ->tag('controller.service_arguments');

    // Environment Variable Processor pour construire MAILER_DSN depuis PREVARISC_MAIL_*
    $services->set('App\DependencyInjection\PrevariscMailerDsnProcessor')
        ->tag('container.env_var_processor');

    $services->set('App\Controller\EtablissementController')
        ->arg('$nbDossiersAAfficher', '%app.nb_dossiers_a_afficher%')
        ->arg('$descriptifPersonnalise', '%app.descriptif_personnalise%');

    $services->set('App\Form\UtilisateurType')
        ->arg('$enforceSecurity', '%app.enforce_security%');
    $services->set('App\Form\CommissionRegleType')
        ->arg('$commissionCommunaleType', '%app.commission_communale_type%');
    $services->set('App\Form\DescriptifOriginalType')
        ->arg('$risquesNaturelsExemples', '%app.risques_naturels_exemples%')
        ->arg('$risquesTechnologiquesExemples', '%app.risques_technologiques_exemples%');

    $services->set('App\Validator\PasswordValidator')
        ->arg('$enforceSecurity', '%app.enforce_security%');

    $services->set('App\Service\Authentification')
        ->arg('$casEnabled', '%app.cas_enabled%')
        ->arg('$ntlmEnabled', '%app.ntlm_enabled%')
        ->arg('$ldapEnabled', '%app.ldap_enabled%');

    $services->set('App\Security\Md5SaltedEncoder')
        ->arg('$globalSalt', '%app.security_salt%');

    // Service LDAP partagé : connexion lazy, évite une nouvelle instance à chaque tentative d'auth.
    // Utilisé uniquement si PREVARISC_LDAP_ENABLED=1.
    $services->set('app.ldap', Ldap::class)
        ->factory([Ldap::class, 'create'])
        ->args([
            'ext_ldap',
            ['connection_string' => 'ldap://%app.ldap_host%:%app.ldap_port%'],
        ]);

    $services->set('App\Security\LogoutHandler')
        ->arg('$casEnabled', '%app.cas_enabled%');

    $services->set('App\Security\CasLogoutSuccessHandler')
        ->arg('$casEnabled', '%app.cas_enabled%')
        ->arg('$casHost', '%app.cas_host%')
        ->arg('$casPort', '%app.cas_port%')
        ->arg('$casPublicHost', '%app.cas_public_host%')
        ->arg('$casPublicPort', '%app.cas_public_port%')
        ->arg('$casContext', '%app.cas_context%')
        ->arg('$casNoSsl', '%app.cas_no_ssl%');

    $services->set('App\Security\FormLoginAuthenticator')
        ->arg('$tokenStorage', ref('security.token_storage'))
        ->arg('$csrfTokenManager', ref('security.csrf.token_manager'))
        ->arg('$symfonyLoginEnabled', '%app.symfony_login%')
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
        ->arg('$ldap', ref('app.ldap'));

    $services->set('App\Service\DataStore\DataStoreFactory')
        ->arg('$adapter', '%app.datastore_adapter%');

    $services->set('App\Service\CouchesCarto')
        ->arg('$cache', ref('cache.ign_capabilities'));

    $services->set('App\Service\Etablissement');

    $services->set('App\Service\EtablissementPieceJointeService')
        ->arg('$datastoreLogger', ref('monolog.logger.datastore'));

    $services->set('App\Service\ProxyConfigurationService')
        ->arg('$proxyEnabled', '%app.proxy_enabled%')
        ->arg('$proxyProtocol', '%app.proxy_protocol%')
        ->arg('$proxyPort', '%app.proxy_port%')
        ->arg('$proxyHost', '%app.proxy_host%')
        ->arg('$proxyUsername', '%app.proxy_username%')
        ->arg('$proxyPassword', '%app.proxy_password%');

    $services->set('App\Controller\SearchController')
        ->arg('$deactivatePlatau', '%app.deactivate_platau%')
        ->tag('controller.service_arguments');

    $services->set('App\Controller\IndexController')
        ->arg('$deactivatePlatau', '%app.deactivate_platau%')
        ->arg('$platauNoHealthcheck', '%app.platau_no_healthcheck%')
        ->tag('controller.service_arguments');

    $services->set('App\Service\Dashboard\DashboardBlocConfig')
        ->arg('$deactivatePlatau', '%app.deactivate_platau%')
        ->arg('$nextCommissionsDays', '%app.dashboard.next_commissions_days%')
        ->arg('$dossiersSansAvisDays', '%app.dashboard.dossiers_sans_avis_days%')
        ->arg('$courrierSansReponseDays', '%app.dashboard.courrier_sans_reponse_days%');

    $services->set('App\Service\PlatauHealthcheckService')
        ->arg('$platauConfigFile', '%app.platau_config_file%')
        ->arg('$projectDir', '%kernel.project_dir%')
        ->arg('$appEnv', '%kernel.environment%');

    $services->set('App\Service\Mail\MailerConfiguration')
        ->arg('$transport', '%app.mail_transport%')
        ->arg('$host', '%app.mail_host%')
        ->arg('$port', '%app.mail_port%')
        ->arg('$username', '%app.mail_username%')
        ->arg('$password', '%app.mail_password%')
        ->arg('$sender', '%app.mail_sender%')
        ->arg('$senderName', '%app.mail_sender_name%')
        ->arg('$enabled', '%app.mail_enabled%');
};
