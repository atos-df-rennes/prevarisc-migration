<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $version = $composer['version'] ?? null;
    if (!is_string($version) || '' === $version) {
        throw new LogicException('La clé "version" est absente ou invalide dans composer.json.');
    }

    $containerConfigurator->extension('framework', [
        'secret' => '%env(APP_SECRET)%',
        'session' => [
            'handler_id' => null,
            'storage_factory_id' => 'session.storage.factory.native',
            'cookie_secure' => 'auto',
            'cookie_samesite' => 'lax',
        ],
        'esi' => true,
        'fragments' => [
            'path' => '/_fragment',
        ],
        'php_errors' => [
            'log' => true,
            'throw' => false,
        ],
        'http_method_override' => true,
        'handle_all_throwables' => true,
        'assets' => [
            'version' => 'v'.$version,
        ],
    ]);
};
