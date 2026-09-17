<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Security\FormLoginAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class FormLoginAuthenticatorTest extends TestCase
{
    /**
     * Vérifie que la migration MD5 → bcrypt/argon2 est effective au login :
     * une fois le mot de passe legacy validé via migrate_from, le nouveau hash
     * doit être calculé et persisté immédiatement (CustomCredentials ne
     * déclenche pas le mécanisme automatique PasswordUpgradeBadge de Symfony,
     * l'upgrade doit donc être fait manuellement dans checkCredentials()).
     */
    public function testCheckCredentialsUpgradesLegacyPasswordOnSuccessfulLogin(): void
    {
        $utilisateur = new Utilisateur();
        $utilisateur->setUsername('jsmith');
        $utilisateur->setSso(false);
        $utilisateur->setPassword('legacy-md5-hash');

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->once())->method('isPasswordValid')->with($utilisateur, 'secret')->willReturn(true);
        $hasher->expects($this->once())->method('needsRehash')->with($utilisateur)->willReturn(true);
        $hasher->expects($this->once())->method('hashPassword')->with($utilisateur, 'secret')->willReturn('new-bcrypt-hash');

        $repository = $this->createMock(UtilisateurRepository::class);
        $repository->expects($this->once())->method('upgradePassword')->with($utilisateur, 'new-bcrypt-hash');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('getRepository')->with(Utilisateur::class)->willReturn($repository);

        $authenticator = $this->createAuthenticator($hasher, $entityManager);

        $isValid = $authenticator->checkCredentials(
            ['username' => 'jsmith', 'password' => 'secret', 'sso_type' => null],
            $utilisateur
        );

        $this->assertTrue($isValid);
    }

    public function testCheckCredentialsDoesNotUpgradeWhenHashAlreadyModern(): void
    {
        $utilisateur = new Utilisateur();
        $utilisateur->setUsername('jsmith');
        $utilisateur->setSso(false);
        $utilisateur->setPassword('already-bcrypt-hash');

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->once())->method('isPasswordValid')->willReturn(true);
        $hasher->expects($this->once())->method('needsRehash')->with($utilisateur)->willReturn(false);
        $hasher->expects($this->never())->method('hashPassword');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('getRepository');

        $authenticator = $this->createAuthenticator($hasher, $entityManager);

        $isValid = $authenticator->checkCredentials(
            ['username' => 'jsmith', 'password' => 'secret', 'sso_type' => null],
            $utilisateur
        );

        $this->assertTrue($isValid);
    }

    public function testCheckCredentialsDoesNotUpgradeOnInvalidPassword(): void
    {
        $utilisateur = new Utilisateur();
        $utilisateur->setUsername('jsmith');
        $utilisateur->setSso(false);
        $utilisateur->setPassword('legacy-md5-hash');

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->once())->method('isPasswordValid')->willReturn(false);
        $hasher->expects($this->never())->method('needsRehash');
        $hasher->expects($this->never())->method('hashPassword');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('getRepository');

        $authenticator = $this->createAuthenticator($hasher, $entityManager);

        $isValid = $authenticator->checkCredentials(
            ['username' => 'jsmith', 'password' => 'wrong', 'sso_type' => null],
            $utilisateur
        );

        $this->assertFalse($isValid);
    }

    private function createAuthenticator(UserPasswordHasherInterface $hasher, EntityManagerInterface $entityManager): FormLoginAuthenticator
    {
        return new FormLoginAuthenticator(
            new NullLogger(),
            $hasher,
            $this->createStub(RouterInterface::class),
            $entityManager,
            $this->createStub(TokenStorageInterface::class),
            $this->createStub(CsrfTokenManagerInterface::class),
            false, // casEnabled
            '',
            0,
            '',
            0,
            '',
            false,
            false,
            '',
            false, // ntlmEnabled
            false, // ldapEnabled
            '',
            0,
            '',
            '',
            '',
            '',
            '',
            false, // enforceSecurity
        );
    }
}
