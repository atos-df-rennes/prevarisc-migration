<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
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

    $services->set('App\Service\DataStore\DataStoreFactory')
        ->arg('$adapter', '%app.datastore_adapter%');

    $services->set('App\Service\Etablissement')
        ->arg('$datastoreLogger', ref('monolog.logger.datastore'));

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
