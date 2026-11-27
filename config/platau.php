<?php

declare(strict_types=1);

use App\Platau\PecDocuments;
use App\Platau\PisteTokenProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('env(PREVARISC_DEACTIVATE_PLATAU)', 0);

    $parameters->set('app.deactivate_platau', '%env(bool:PREVARISC_DEACTIVATE_PLATAU)%');

    $parameters->set('env(PLATAU_CONFIG_FILE)', 'config.json');

    $parameters->set('app.platau_config_file', '%env(string:PLATAU_CONFIG_FILE)%');

    $parameters->set('env(PREVARISC_PLATAU_NO_HEALTHCHECK)', 0);

    $parameters->set('app.platau_no_healthcheck', '%env(bool:PREVARISC_PLATAU_NO_HEALTHCHECK)%');

    $parameters->set('env(PREVARISC_PLATAU_HEALTHCHECK_CACHE_TTL)', 300);

    $parameters->set('app.platau_healthcheck_cache_ttl', '%env(int:PREVARISC_PLATAU_HEALTHCHECK_CACHE_TTL)%');

    // Configuration intégrée, indépendante du fichier JSON du tableau de bord.
    $parameters->set('env(PISTE_CLIENT_ID)', '');
    $parameters->set('env(PISTE_CLIENT_SECRET)', '');
    $parameters->set('env(PLATAU_ID_ACTEUR_APPELANT)', '');
    $parameters->set('env(PREVARISC_DB_PLATAU_USER_ID)', '');
    $parameters->set('env(PISTE_ACCESS_TOKEN_URL)', 'https://oauth.piste.gouv.fr/api/oauth/token');
    $parameters->set('env(PLATAU_URL)', 'https://api.piste.gouv.fr/mtes/platau/v13/dau/');

    $parameters->set('env(SYNCPLICITY_PISTE_CLIENT_ID)', '');
    $parameters->set('env(SYNCPLICITY_PISTE_CLIENT_SECRET)', '');
    $parameters->set('env(SYNCPLICITY_PISTE_ACCESS_TOKEN_URL)', '%env(PISTE_ACCESS_TOKEN_URL)%');
    $parameters->set('env(SYNCPLICITY_URL)', 'https://api.piste.gouv.fr/syncplicity/upload/');
    $containerConfigurator->services()->set('platau.syncplicity_tokens', PisteTokenProvider::class)
        ->autowire()
        ->arg('$pisteClient', service('syncplicity.piste_client'))
        ->arg('$cache', service('cache.syncplicity_tokens'))
        ->arg('$clientId', '%env(SYNCPLICITY_PISTE_CLIENT_ID)%')
        ->arg('$clientSecret', '%env(SYNCPLICITY_PISTE_CLIENT_SECRET)%')
        ->arg('$tokenUrl', '%env(SYNCPLICITY_PISTE_ACCESS_TOKEN_URL)%');
    if ('test' === $containerConfigurator->env()) {
        $containerConfigurator->services()->alias('test.pec_documents', PecDocuments::class)->public();
    }

    // Le transport standard journalise l'URL complète, y compris en console
    // verbeuse. Une URL de stockage signée ne doit jamais y apparaître.
    $containerConfigurator->services()->set('platau.piece_transport', HttpClientInterface::class)
        ->factory(HttpClient::create(...));
};
