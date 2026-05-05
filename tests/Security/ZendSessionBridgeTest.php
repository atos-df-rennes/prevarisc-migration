<?php

namespace App\Tests\Security;

use App\Security\ZendSessionBridge;
use PHPUnit\Framework\TestCase;

class ZendSessionBridgeTest extends TestCase
{
    /** @var ZendSessionBridge */
    private $bridge;

    protected function setUp(): void
    {
        $this->bridge = new ZendSessionBridge();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testGetAuthenticatedUsernameReturnsNullWhenSessionAbsente(): void
    {
        $result = $this->bridge->getAuthenticatedUsername();

        $this->assertNull($result);
    }

    public function testIsAuthenticatedReturnsFalseWhenSessionAbsente(): void
    {
        $result = $this->bridge->isAuthenticated();

        $this->assertFalse($result);
    }

    public function testGetAuthenticatedUsernameReturnsNullWhenUsernameUtilisateurAbsent(): void
    {
        $_SESSION['Zend_Auth'] = ['storage' => []];

        $result = $this->bridge->getAuthenticatedUsername();

        $this->assertNull($result);
    }

    public function testIsAuthenticatedReturnsFalseWhenUsernameUtilisateurAbsent(): void
    {
        $_SESSION['Zend_Auth'] = ['storage' => []];

        $result = $this->bridge->isAuthenticated();

        $this->assertFalse($result);
    }

    public function testGetAuthenticatedUsernameReturnsUsernameWhenPresent(): void
    {
        $_SESSION['Zend_Auth'] = ['storage' => ['USERNAME_UTILISATEUR' => 'jdupont']];

        $result = $this->bridge->getAuthenticatedUsername();

        $this->assertSame('jdupont', $result);
    }

    public function testIsAuthenticatedReturnsTrueWhenUsernamePresent(): void
    {
        $_SESSION['Zend_Auth'] = ['storage' => ['USERNAME_UTILISATEUR' => 'jdupont']];

        $result = $this->bridge->isAuthenticated();

        $this->assertTrue($result);
    }

    public function testWriteAuthenticationCreatesZendAuthStructureWhenAbsente(): void
    {
        $identity = ['USERNAME_UTILISATEUR' => 'jdupont', 'ROLE' => 'admin'];

        $this->bridge->writeAuthentication($identity);

        $this->assertSame($identity, $_SESSION['Zend_Auth']['storage']);
    }

    public function testWriteAuthenticationOverridesExistingStorage(): void
    {
        $_SESSION['Zend_Auth'] = ['storage' => ['USERNAME_UTILISATEUR' => 'ancien']];
        $identity = ['USERNAME_UTILISATEUR' => 'nouveau'];

        $this->bridge->writeAuthentication($identity);

        $this->assertSame($identity, $_SESSION['Zend_Auth']['storage']);
    }

    public function testWriteAuthenticationPreservesOtherZendAuthKeys(): void
    {
        $_SESSION['Zend_Auth'] = ['autreClef' => 'valeur', 'storage' => []];
        $identity = ['USERNAME_UTILISATEUR' => 'jdupont'];

        $this->bridge->writeAuthentication($identity);

        $this->assertSame('valeur', $_SESSION['Zend_Auth']['autreClef']);
        $this->assertSame($identity, $_SESSION['Zend_Auth']['storage']);
    }

    public function testClearAuthenticationRemovesZendAuthFromSession(): void
    {
        $_SESSION['Zend_Auth'] = ['storage' => ['USERNAME_UTILISATEUR' => 'jdupont']];

        $this->bridge->clearAuthentication();

        $this->assertArrayNotHasKey('Zend_Auth', $_SESSION);
    }

    public function testClearAuthenticationIsIdempotentWhenSessionAbsente(): void
    {
        $this->bridge->clearAuthentication();

        $this->assertArrayNotHasKey('Zend_Auth', $_SESSION);
    }
}
