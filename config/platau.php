<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('env(PREVARISC_DEACTIVATE_PLATAU)', 0);

    $parameters->set('app.deactivate_platau', '%env(bool:PREVARISC_DEACTIVATE_PLATAU)%');

    $parameters->set('env(PLATAU_CONFIG_FILE)', 'config.json');

    $parameters->set('app.platau_config_file', '%env(string:PLATAU_CONFIG_FILE)%');
};
