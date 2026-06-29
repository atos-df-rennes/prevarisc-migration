<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Utilisateur;
use App\Security\LogoutHandler;
use App\Security\ZendSessionBridge;
use App\Service\LastActionUpdater;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class LogoutHandlerTest extends TestCase
{
    /** @var ZendSessionBridge|MockObject */
    private $zendSessionBridge;

    /** @var LastActionUpdater|MockObject */
    private $lastActionUpdater;

    protected function setUp(): void
    {
        $this->zendSessionBridge = $this->createMock(ZendSessionBridge::class);
        $this->lastActionUpdater = $this->createMock(LastActionUpdater::class);
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    private function buildHandler(bool $casEnabled = false): LogoutHandler
    {
        return new LogoutHandler($this->zendSessionBridge, $this->lastActionUpdater, $casEnabled);
    }

    private function buildRequest(): Request
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->getSession()->set('username', 'jdupont');

        return $request;
    }

    public function testLogoutUpdatesLastActionForUtilisateur(): void
    {
        $utilisateur = new Utilisateur();

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($utilisateur);

        $this->lastActionUpdater->expects($this->once())->method('update')->with($utilisateur);

        $this->buildHandler()->logout($this->buildRequest(), new Response(), $token);
    }

    public function testLogoutSkipsLastActionWhenUserIsNotUtilisateur(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->lastActionUpdater->expects($this->never())->method('update');

        $this->buildHandler()->logout($this->buildRequest(), new Response(), $token);
    }

    public function testLogoutSkipsLastActionWhenUserIsString(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn('anonymous');

        $this->lastActionUpdater->expects($this->never())->method('update');

        $this->buildHandler()->logout($this->buildRequest(), new Response(), $token);
    }

    public function testLogoutClearsZendAuthentication(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->zendSessionBridge->expects($this->once())->method('clearAuthentication');

        $this->buildHandler()->logout($this->buildRequest(), new Response(), $token);
    }

    public function testLogoutRemovesUsernameFromSession(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $request = $this->buildRequest();

        $this->buildHandler()->logout($request, new Response(), $token);

        $this->assertFalse($request->getSession()->has('username'));
    }

    public function testLogoutClearsPhpCasSessionWhenCasEnabled(): void
    {
        $_SESSION['phpCAS'] = ['ticket' => 'ST-123'];

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->buildHandler(true)->logout($this->buildRequest(), new Response(), $token);

        $this->assertArrayNotHasKey('phpCAS', $_SESSION);
    }

    public function testLogoutKeepsPhpCasSessionWhenCasDisabled(): void
    {
        $_SESSION['phpCAS'] = ['ticket' => 'ST-123'];

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->buildHandler(false)->logout($this->buildRequest(), new Response(), $token);

        $this->assertArrayHasKey('phpCAS', $_SESSION);
    }
}
