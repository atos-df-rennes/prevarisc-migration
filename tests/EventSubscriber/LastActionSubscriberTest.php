<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Entity\Utilisateur;
use App\EventSubscriber\LastActionSubscriber;
use App\Service\LastActionUpdater;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class LastActionSubscriberTest extends TestCase
{
    /** @var Stub&Security */
    private Security $security;

    /** @var MockObject&LastActionUpdater */
    private MockObject $lastActionUpdater;

    private LastActionSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->security = $this->createStub(Security::class);
        $this->lastActionUpdater = $this->createMock(LastActionUpdater::class);
        $this->subscriber = new LastActionSubscriber($this->security, $this->lastActionUpdater);
    }

    private function buildTerminateEvent(bool $isMasterRequest = true): TerminateEvent
    {
        $requestType = $isMasterRequest
            ? HttpKernelInterface::MAIN_REQUEST
            : HttpKernelInterface::SUB_REQUEST;

        return new TerminateEvent($this->createStub(HttpKernelInterface::class), new Request(), new Response(), $requestType);
    }

    public function testSubscribesToKernelTerminate(): void
    {
        $this->lastActionUpdater->expects($this->never())->method('update');

        $events = LastActionSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey('kernel.terminate', $events);
    }

    public function testSkipsSubRequests(): void
    {
        $this->lastActionUpdater->expects($this->never())->method('update');

        $this->subscriber->onKernelTerminate($this->buildTerminateEvent(false));
    }

    public function testSkipsWhenUserIsNull(): void
    {
        $this->security->method('getUser')->willReturn(null);
        $this->lastActionUpdater->expects($this->never())->method('update');

        $this->subscriber->onKernelTerminate($this->buildTerminateEvent());
    }

    public function testSkipsWhenUserIsNotUtilisateur(): void
    {
        $this->security->method('getUser')->willReturn($this->createStub(UserInterface::class));
        $this->lastActionUpdater->expects($this->never())->method('update');

        $this->subscriber->onKernelTerminate($this->buildTerminateEvent());
    }

    public function testCallsUpdateForUtilisateur(): void
    {
        $utilisateur = new Utilisateur();
        $this->security->method('getUser')->willReturn($utilisateur);

        $this->lastActionUpdater->expects($this->once())->method('update')->with($utilisateur);

        $this->subscriber->onKernelTerminate($this->buildTerminateEvent());
    }
}
