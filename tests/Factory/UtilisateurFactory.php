<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Utilisateur;
use App\Entity\UtilisateurPreferences;
use App\Tests\Story\GroupeDefautStory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Utilisateur>
 */
final class UtilisateurFactory extends PersistentObjectFactory
{
    public const string DEFAULT_PASSWORD = 'Fixture-only-password-123!';

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public static function class(): string
    {
        return Utilisateur::class;
    }

    public function preventionniste(): self
    {
        return $this->with(['informations' => UtilisateurInformationsFactory::new()->preventionniste()]);
    }

    protected function defaults(): array
    {
        return [
            'username' => self::faker()->unique()->userName(),
            'plainPassword' => self::DEFAULT_PASSWORD,
            'actif' => true,
            'sso' => false,
            'roles' => [],
            'groupe' => GroupeDefautStory::groupe(),
            'informations' => UtilisateurInformationsFactory::new(),
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function (Utilisateur $utilisateur): void {
            $utilisateur->setPassword($this->passwordHasher->hashPassword($utilisateur, $utilisateur->getPlainPassword()));
            $utilisateur->setPreferences($utilisateur->getPreferences() ?? new UtilisateurPreferences());
        });
    }
}
