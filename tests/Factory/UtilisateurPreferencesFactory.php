<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\UtilisateurPreferences;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<UtilisateurPreferences>
 */
final class UtilisateurPreferencesFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return UtilisateurPreferences::class;
    }

    protected function defaults(): array
    {
        return [
            'utilisateur' => UtilisateurFactory::new()->withoutPersisting(),
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(static function (UtilisateurPreferences $preferences): void {
            $preferences->getUtilisateur()->setPreferences($preferences);
        });
    }
}
