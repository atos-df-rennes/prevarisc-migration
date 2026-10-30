<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Controller\Contract\DossierPrescriptionsAccessInterface;
use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\DossierType;
use App\EventSubscriber\PrescriptionsAccessSubscriber;
use App\Repository\DossierRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class PrescriptionsAccessSubscriberTest extends TestCase
{
    /** @var MockObject&DossierRepository */
    private MockObject $dossierRepository;

    private PrescriptionsAccessSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->dossierRepository = $this->createMock(DossierRepository::class);
        $this->subscriber = new PrescriptionsAccessSubscriber($this->dossierRepository);
    }

    public function testSkipsControllerNotImplementingInterface(): void
    {
        $this->dossierRepository->expects($this->never())->method('find');

        $this->subscriber->onKernelController($this->buildEvent(function (): void {}, 1));
    }

    public function testThrowsNotFoundWhenDossierDoesNotExist(): void
    {
        $this->dossierRepository->expects($this->once())->method('find')->with(1)->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->subscriber->onKernelController($this->buildEvent($this->prescriptionsController(), 1));
    }

    public function testThrowsNotFoundWhenNatureSansPrescriptions(): void
    {
        // Dérogation
        $dossier = $this->creerDossier($this->creerNature(31), $this->creerType(1));
        $this->dossierRepository->expects($this->once())->method('find')->with(1)->willReturn($dossier);

        $this->expectException(NotFoundHttpException::class);

        $this->subscriber->onKernelController($this->buildEvent($this->prescriptionsController(), 1));
    }

    public function testThrowsNotFoundWhenTypeSansPrescriptions(): void
    {
        // Courrier / Courriel
        $dossier = $this->creerDossier($this->creerNature(1), $this->creerType(5));
        $this->dossierRepository->expects($this->once())->method('find')->with(1)->willReturn($dossier);

        $this->expectException(NotFoundHttpException::class);

        $this->subscriber->onKernelController($this->buildEvent($this->prescriptionsController(), 1));
    }

    public function testAllowsAccessWhenNatureEtTypeAutorisent(): void
    {
        $dossier = $this->creerDossier($this->creerNature(1), $this->creerType(1));
        $this->dossierRepository->expects($this->once())->method('find')->with(1)->willReturn($dossier);

        $this->subscriber->onKernelController($this->buildEvent($this->prescriptionsController(), 1));

        $this->addToAssertionCount(1);
    }

    private function prescriptionsController(): DossierPrescriptionsAccessInterface
    {
        return new class implements DossierPrescriptionsAccessInterface {
            public function __invoke(): void
            {
            }
        };
    }

    private function buildEvent(\Closure|DossierPrescriptionsAccessInterface $controller, int $idDossier): ControllerEvent
    {
        $request = new Request();
        $request->attributes->set('id', $idDossier);

        return new ControllerEvent($this->createStub(HttpKernelInterface::class), $controller, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    private function creerDossier(DossierNatureListe $nature, DossierType $type): Dossier
    {
        $dossier = new Dossier();
        $dossier->setNature($nature);
        $dossier->setType($type);

        return $dossier;
    }

    private function creerNature(int $id): DossierNatureListe
    {
        $nature = new DossierNatureListe();

        $reflection = new \ReflectionClass($nature);
        $property = $reflection->getProperty('id');
        $property->setValue($nature, $id);

        return $nature;
    }

    private function creerType(int $id): DossierType
    {
        $type = new DossierType();

        $reflection = new \ReflectionClass($type);
        $property = $reflection->getProperty('id');
        $property->setValue($type, $id);

        return $type;
    }
}
