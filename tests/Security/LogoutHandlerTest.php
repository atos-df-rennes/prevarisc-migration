<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Utilisateur;
use App\Security\LogoutHandler;
use App\Service\LastActionUpdater;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class LogoutHandlerTest extends TestCase
{
    /** @var MockObject&LastActionUpdater */
    private MockObject $lastActionUpdater;

    protected function setUp(): void
    {
        $this->lastActionUpdater = $this->createMock(LastActionUpdater::class);
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    private function buildHandler(bool $casEnabled = false): LogoutHandler
    {
        return new LogoutHandler($this->lastActionUpdater, $casEnabled);
    }

    private function buildRequest(): Request
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    private function buildLogoutEvent(?TokenInterface $token): LogoutEvent
    {
        return new LogoutEvent($this->buildRequest(), $token);
    }

    public function testLogoutUpdatesLastActionForUtilisateur(): void
    {
        $utilisateur = new Utilisateur();

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($utilisateur);

        $this->lastActionUpdater->expects($this->once())->method('update')->with($utilisateur);

        $this->buildHandler()->onLogout($this->buildLogoutEvent($token));
    }

    public function testLogoutSkipsLastActionWhenUserIsNotUtilisateur(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->lastActionUpdater->expects($this->never())->method('update');

        $this->buildHandler()->onLogout($this->buildLogoutEvent($token));
    }

    public function testLogoutSkipsLastActionWhenUserIsNotUtilisateurInstance(): void
    {
        // TokenInterface::getUser() is now strictly typed as ?UserInterface (Symfony 6.4),
        // so a string return is no longer possible. This exercises the equivalent branch:
        // a UserInterface implementation that is not an App\Entity\Utilisateur.
        $otherUser = $this->createStub(UserInterface::class);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($otherUser);

        $this->lastActionUpdater->expects($this->never())->method('update');

        $this->buildHandler()->onLogout($this->buildLogoutEvent($token));
    }

    public function testLogoutClearsPhpCasSessionWhenCasEnabled(): void
    {
        $_SESSION['phpCAS'] = ['ticket' => 'ST-123'];

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->buildHandler(true)->onLogout($this->buildLogoutEvent($token));

        $this->assertArrayNotHasKey('phpCAS', $_SESSION);
    }

    public function testLogoutKeepsPhpCasSessionWhenCasDisabled(): void
    {
        $_SESSION['phpCAS'] = ['ticket' => 'ST-123'];

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->buildHandler(false)->onLogout($this->buildLogoutEvent($token));

        $this->assertArrayHasKey('phpCAS', $_SESSION);
    }
}
