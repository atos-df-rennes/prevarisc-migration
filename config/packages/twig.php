<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('twig', [
        'default_path' => '%kernel.project_dir%/templates',
        'debug' => '%kernel.debug%',
        'strict_variables' => '%kernel.debug%',
        'exception_controller' => null,
        'form_themes' => [
            'bootstrap_3_layout.html.twig',
        ],
        'globals' => [
            // Portage Symfony du plugin legacy Plugin_SDIS29 (cf. PREVARISC_THIRDPARTY_PLUGINS)
            'sdis29_custom_enabled' => '%app.sdis29_custom_enabled%',
            'geobretagne_url' => '%app.geobretagne_url%',
            // Portages Symfony des plugins legacy de personnalisation de libellés (cf. PREVARISC_THIRDPARTY_PLUGINS)
            'facteur_criticite_custom_enabled' => '%app.facteur_criticite_custom_enabled%',
            'libelle_dossier_observations_custom_enabled' => '%app.libelle_dossier_observations_custom_enabled%',
            'libelle_dossier_prescriptions_custom_enabled' => '%app.libelle_dossier_prescriptions_custom_enabled%',
        ],
    ]);
};
