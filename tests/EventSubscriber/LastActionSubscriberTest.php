<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Entity\Utilisateur;
use App\EventSubscriber\LastActionSubscriber;
use App\Service\LastActionUpdater;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Security;

final class LastActionSubscriberTest extends TestCase
{
    /** @var Security|MockObject */
    private MockObject $security;

    /** @var LastActionUpdater|MockObject */
    private MockObject $lastActionUpdater;

    private LastActionSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->lastActionUpdater = $this->createMock(LastActionUpdater::class);
        $this->subscriber = new LastActionSubscriber($this->security, $this->lastActionUpdater);
    }

    private function buildTerminateEvent(bool $isMasterRequest = true): TerminateEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $requestType = $isMasterRequest
            ? HttpKernelInterface::MASTER_REQUEST
            : HttpKernelInterface::SUB_REQUEST;

        return new TerminateEvent($kernel, new Request(), new Response(), $requestType);
    }

    public function testSubscribesToKernelTerminate(): void
    {
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
        $this->security->method('getUser')->willReturn(new \stdClass());
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
