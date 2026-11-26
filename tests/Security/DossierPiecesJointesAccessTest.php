<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Controller\Dossier\DossierPiecesJointesController;
use App\Entity\Dossier;
use App\Entity\PieceJointe;
use App\Repository\DossierPieceJointeRepository;
use App\Repository\PieceJointeStatutRepository;
use App\Service\DataStore\DataStore;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\EventListener\IsGrantedAttributeListener;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;

final class DossierPiecesJointesAccessTest extends TestCase
{
    #[DataProvider('accessScenarios')]
    public function testActionsRequireBothPrivileges(string $action, bool $canView, bool $canModifyAgenda): void
    {
        $dossier = new Dossier();
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $checker->expects($this->exactly($canView ? 2 : 1))->method('isGranted')->willReturnCallback(
            static fn (mixed $attribute, mixed $subject): bool => match ($attribute) {
                'view_doss' => $canView && $subject === $dossier,
                'modification_odj' => $canModifyAgenda && null === $subject,
                default => false,
            }
        );

        $event = new ControllerArgumentsEvent(
            $this->createStub(HttpKernelInterface::class),
            [$this->controller(), $action],
            [$dossier],
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
        );

        if (!$canView || !$canModifyAgenda) {
            $this->expectException(AccessDeniedException::class);
        }

        new IsGrantedAttributeListener($checker)->onKernelControllerArguments($event);
    }

    public static function accessScenarios(): iterable
    {
        foreach (['index', 'form', 'add', 'delete', 'check'] as $action) {
            yield "$action : les deux droits" => [$action, true, true];
            yield "$action : lecture seule" => [$action, true, false];
            yield "$action : ordre du jour seul" => [$action, false, true];
            yield "$action : aucun droit" => [$action, false, false];
        }
    }

    #[DataProvider('lockedActions')]
    public function testLockedDossierRejectsModification(string $action): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects('add' === $action ? $this->once() : $this->never())
            ->method('warning')
            ->with("Ajout d'une pièce jointe refusé : le dossier est verrouillé.", ['dossierId' => null]);
        $controller = $this->controller(logger: $logger);
        $dossier = new Dossier()->setVerrou(true);
        $request = new Request();
        $pieceJointe = new PieceJointe();

        $this->expectException(AccessDeniedException::class);

        match ($action) {
            'form' => $controller->form($dossier),
            'add' => $controller->add($dossier, $request),
            'delete' => $controller->delete($dossier, $pieceJointe, $request),
            'check' => $controller->check($dossier, $pieceJointe, $request),
        };
    }

    public static function lockedActions(): iterable
    {
        foreach (['form', 'add', 'delete', 'check'] as $action) {
            yield $action => [$action];
        }
    }

    public function testUnlockedDossierAllowsOpeningForm(): void
    {
        $controller = $this->controller();
        $twig = $this->createStub(Environment::class);
        $twig->method('render')->willReturn('formulaire');
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($twig);
        $controller->setContainer($container);

        $response = $controller->form(new Dossier()->setVerrou(false));

        $this->assertSame('formulaire', $response->getContent());
    }

    private function controller(?DataStore $dataStore = null, ?EntityManagerInterface $entityManager = null, ?ValidatorInterface $validator = null, ?LoggerInterface $logger = null): DossierPiecesJointesController
    {
        $entityManager ??= $this->createStub(EntityManagerInterface::class);

        return new DossierPiecesJointesController(
            $dataStore ?? $this->createStub(DataStore::class),
            $this->createStub(DossierPieceJointeRepository::class),
            $this->createStub(PieceJointeStatutRepository::class),
            $entityManager,
            new NotificationService($entityManager),
            $validator ?? $this->createStub(ValidatorInterface::class),
            $this->createStub(Filesystem::class),
            '',
            $logger ?? $this->createStub(LoggerInterface::class),
        );
    }
}
