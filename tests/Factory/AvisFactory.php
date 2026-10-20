<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Avis;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Avis>
 */
final class AvisFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Avis::class;
    }

    protected function defaults(): array
    {
        return [
            'libelle' => self::faker()->words(3, true),
        ];
    }
}
