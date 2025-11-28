<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use App\Service\DataStore\DataStore;
use App\Service\DataStore\RuntimeDataStore;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DataStoreCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->setAlias(DataStore::class, RuntimeDataStore::class)->setPublic(true);
    }
}
