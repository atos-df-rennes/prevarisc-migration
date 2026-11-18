<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Security\FormLoginAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Ldap\Adapter\AdapterInterface;
use Symfony\Component\Ldap\Adapter\ConnectionInterface;
use Symfony\Component\Ldap\Exception\InvalidCredentialsException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

#[AllowMockObjectsWithoutExpectations]
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

    #[DataProvider('provideAuthenticationRoutes')]
    public function testSelectsExactlyOnePasswordVerifier(bool $sso, bool $ldapEnabled, bool $passwordValid): void
    {
        $utilisateur = new Utilisateur()->setUsername('jsmith')->setSso($sso)->setPassword('local-hash');
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($sso ? $this->never() : $this->once())
            ->method('isPasswordValid')->with($utilisateur, 'secret')->willReturn($passwordValid);
        $hasher->method('needsRehash')->willReturn(false);

        $connection = $this->createMock(ConnectionInterface::class);
        $bind = $connection->expects($sso && $ldapEnabled ? $this->once() : $this->never())
            ->method('bind')->with('jsmith', 'secret');
        if (!$passwordValid) {
            $bind->willThrowException(new InvalidCredentialsException('Invalid credentials'));
        }

        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('getConnection')->willReturn($connection);

        $authenticator = $this->createAuthenticator(
            $hasher,
            $this->createStub(EntityManagerInterface::class),
            ldapEnabled: $ldapEnabled,
            ldap: new Ldap($adapter)
        );

        $this->assertSame($passwordValid && (!$sso || $ldapEnabled), $authenticator->checkCredentials(
            ['username' => 'jsmith', 'password' => 'secret', 'sso_type' => null],
            $utilisateur
        ));
    }

    public static function provideAuthenticationRoutes(): iterable
    {
        yield 'LDAP success ignores local hash' => [true, true, true];
        yield 'LDAP failure never checks local hash' => [true, true, false];
        yield 'local success with LDAP enabled' => [false, true, true];
        yield 'local failure never tries LDAP' => [false, true, false];
        yield 'SSO without LDAP is refused' => [true, false, true];
        yield 'local without LDAP' => [false, false, true];
    }

    #[DataProvider('provideEmptyCredentials')]
    public function testRejectsEmptyFormCredentialsBeforeLoadingUser(string $username, string $password): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('getRepository');
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->never())->method('isPasswordValid');
        $csrf = $this->createStub(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('notice')->with('Auth formulaire: identifiant ou mot de passe vide');
        $authenticator = $this->createAuthenticator($hasher, $entityManager, csrf: $csrf, logger: $logger);

        $this->expectException(BadCredentialsException::class);
        $authenticator->authenticate(Request::create('/session/login', 'POST', [
            '_username' => $username,
            '_password' => $password,
        ]));
    }

    #[DataProvider('provideEmptyCredentials')]
    public function testEmptyCredentialsCannotReachLdap(string $username, string $password): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->never())->method('getConnection');
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->never())->method('isPasswordValid');
        $authenticator = $this->createAuthenticator(
            $hasher,
            $this->createStub(EntityManagerInterface::class),
            ldapEnabled: true,
            ldap: new Ldap($adapter)
        );

        $this->assertFalse($authenticator->checkCredentials(
            ['username' => $username, 'password' => $password, 'sso_type' => null],
            new Utilisateur()->setSso(true)
        ));
    }

    public static function provideEmptyCredentials(): iterable
    {
        yield 'empty login' => ['', 'secret'];
        yield 'empty password' => ['jsmith', ''];
        yield 'both empty' => ['', ''];
    }

    #[BackupGlobals(true)]
    public function testNtlmWithoutRemoteUserLeavesFormAvailable(): void
    {
        unset($_SERVER['REMOTE_USER']);
        $csrf = $this->createStub(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);
        $authenticator = $this->createAuthenticator(
            $this->createStub(UserPasswordHasherInterface::class),
            $this->createStub(EntityManagerInterface::class),
            csrf: $csrf,
            ntlmEnabled: true
        );

        $passport = $authenticator->authenticate(Request::create('/session/login', 'POST', [
            '_username' => 'jsmith',
            '_password' => 'secret',
        ]));

        $this->assertSame('jsmith', $passport->getBadge(UserBadge::class)->getUserIdentifier());
    }

    #[DataProvider('provideSsoTypes')]
    public function testDelegatedIdentityDoesNotCheckAnyPassword(string $ssoType): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->never())->method('isPasswordValid');
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->never())->method('getConnection');
        $authenticator = $this->createAuthenticator(
            $hasher,
            $this->createStub(EntityManagerInterface::class),
            ldapEnabled: true,
            ldap: new Ldap($adapter)
        );

        $this->assertTrue($authenticator->checkCredentials(
            ['username' => 'jsmith', 'password' => null, 'sso_type' => $ssoType],
            new Utilisateur()->setSso(false)
        ));
    }

    public static function provideSsoTypes(): iterable
    {
        yield 'CAS' => ['cas'];
        yield 'NTLM' => ['ntlm'];
    }

    public function testLocalAccountWithoutPasswordIsLoggedAndRefused(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->never())->method('isPasswordValid');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('notice')->with('Auth BDD: compte local sans mot de passe', [
            'username' => 'jsmith',
            'ldap_enabled' => true,
        ]);
        $authenticator = $this->createAuthenticator(
            $hasher,
            $this->createStub(EntityManagerInterface::class),
            ldapEnabled: true,
            logger: $logger
        );

        $this->assertFalse($authenticator->checkCredentials(
            ['username' => 'jsmith', 'password' => 'secret', 'sso_type' => null],
            new Utilisateur()->setUsername('jsmith')
        ));
    }

    private function createAuthenticator(
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $entityManager,
        bool $ldapEnabled = false,
        ?Ldap $ldap = null,
        ?CsrfTokenManagerInterface $csrf = null,
        bool $ntlmEnabled = false,
        ?LoggerInterface $logger = null
    ): FormLoginAuthenticator {
        return new FormLoginAuthenticator(
            $logger ?? new NullLogger(),
            $hasher,
            $this->createStub(RouterInterface::class),
            $entityManager,
            $this->createStub(TokenStorageInterface::class),
            $csrf ?? $this->createStub(CsrfTokenManagerInterface::class),
            false, // casEnabled
            '',
            0,
            '',
            0,
            '',
            false,
            false,
            '',
            $ntlmEnabled,
            $ldapEnabled,
            '',
            0,
            '',
            '',
            '',
            '',
            '',
            false, // enforceSecurity
            $ldap
        );
    }
}
