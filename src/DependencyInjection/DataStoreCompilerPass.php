<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use App\Service\DataStore\DataStore;
use App\Service\DataStore\RuntimeDataStore;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass to alias the DataStore interface to the RuntimeDataStore implementation.
 *
 * This enables lazy initialization of the DataStore service by ensuring that
 * all dependencies on DataStore are resolved to RuntimeDataStore at runtime.
 * This approach fits into the application's runtime resolution architecture,
 * allowing for flexible and efficient service instantiation.
 */
class DataStoreCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->setAlias(DataStore::class, RuntimeDataStore::class)->setPublic(true);
    }
}
