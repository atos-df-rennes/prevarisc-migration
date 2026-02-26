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
    ;


    $services->load('App\\', __DIR__ . '/../src/')
        ->exclude([
        __DIR__ . '/../src/DependencyInjection/',
        __DIR__ . '/../src/Entity/',
        __DIR__ . '/../src/Kernel.php',
    ]);

    $services->load('App\Controller\\', __DIR__ . '/../src/Controller/')
        ->tag('controller.service_arguments');

    $services->set('App\Form\UtilisateurType')
        ->arg('$enforceSecurity', '%app.enforce_security%');

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
};
