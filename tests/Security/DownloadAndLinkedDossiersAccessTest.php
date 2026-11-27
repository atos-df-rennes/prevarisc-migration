<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Constant\DataStoreObjectTypes;
use App\Controller\Dossier\DossierAjaxController;
use App\Controller\PieceJointeController;
use App\Entity\DateCommission;
use App\Entity\Etablissement;
use App\Entity\EtablissementPieceJointe;
use App\Entity\PieceJointe;
use App\Repository\DateCommissionRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementPieceJointeRepository;
use App\Repository\PieceJointeRepository;
use App\Service\DataStore\DataStore;
use App\Service\Dossier as DossierService;
use App\Service\PlatauTempsRestant;
use App\Service\TexteApplicable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\EventListener\IsGrantedAttributeListener;

#[AllowMockObjectsWithoutExpectations]
final class DownloadAndLinkedDossiersAccessTest extends TestCase
{
    #[DataProvider('downloadAccessScenarios')]
    public function testDownloadsRequireTheExpectedPrivilege(string $action, bool $granted): void
    {
        $etablissement = new Etablissement();
        $subject = 'getEtablissement' === $action ? $etablissement : null;
        $privilege = 'getEtablissement' === $action ? 'view_ets' : 'lecture_commission';
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $checker->expects($this->once())->method('isGranted')->with($privilege, $subject)->willReturn($granted);
        $controller = new PieceJointeController(
            $this->createStub(DataStore::class),
            $this->createStub(EtablissementPieceJointeRepository::class),
            $this->createStub(PieceJointeRepository::class),
            $this->createStub(DateCommissionRepository::class),
        );
        $arguments = 'getEtablissement' === $action ? [1, 2, $etablissement] : [1, 2];
        $event = new ControllerArgumentsEvent(
            $this->createStub(HttpKernelInterface::class),
            [$controller, $action],
            $arguments,
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
        );

        if (!$granted) {
            $this->expectException(AccessDeniedException::class);
        }

        new IsGrantedAttributeListener($checker)->onKernelControllerArguments($event);
    }

    public static function downloadAccessScenarios(): iterable
    {
        foreach (['getEtablissement', 'getDateCommission'] as $action) {
            yield "$action : autorisé" => [$action, true];
            yield "$action : interdit" => [$action, false];
        }
    }

    #[DataProvider('attachmentScenarios')]
    public function testDownloadRequiresAttachmentToBelongToParent(string $action, bool $attached): void
    {
        $pieceJointe = new PieceJointe()->setNom('document')->setExtension('.pdf');
        $piecesJointes = $this->createMock(PieceJointeRepository::class);
        $piecesJointes->expects($this->once())->method('find')->with(2)->willReturn($pieceJointe);
        $etablissementPiecesJointes = $this->createMock(EtablissementPieceJointeRepository::class);
        $etablissementPiecesJointes->expects('getEtablissement' === $action ? $this->once() : $this->never())
            ->method('findOneBy')->with(['etablissement' => 1, 'pieceJointe' => 2])
            ->willReturn($attached ? new EtablissementPieceJointe() : null);
        $datesCommission = $this->createMock(DateCommissionRepository::class);
        $datesCommission->expects('getDateCommission' === $action ? $this->once() : $this->never())
            ->method('findOneByPieceJointe')->with(1, 2)->willReturn($attached ? new DateCommission() : null);
        $dataStore = $this->createMock(DataStore::class);

        if ($attached) {
            $type = 'getEtablissement' === $action ? DataStoreObjectTypes::ETABLISSEMENT : DataStoreObjectTypes::DATE_COMMISSION;
            $dataStore->expects($this->once())->method('getFilePath')->with($pieceJointe, $type, 1)->willReturn(__FILE__);
            $dataStore->method('getFormattedFilename')->willReturn('document.pdf');
        } else {
            $dataStore->expects($this->never())->method('getFilePath');
            $this->expectException(NotFoundHttpException::class);
        }

        $controller = new PieceJointeController($dataStore, $etablissementPiecesJointes, $piecesJointes, $datesCommission);
        $response = 'getEtablissement' === $action
            ? $controller->getEtablissement(1, 2, new Etablissement())
            : $controller->getDateCommission(1, 2);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
    }

    public static function attachmentScenarios(): iterable
    {
        foreach (['getEtablissement', 'getDateCommission'] as $action) {
            yield "$action : pièce rattachée" => [$action, true];
            yield "$action : pièce non rattachée" => [$action, false];
        }
    }

    #[DataProvider('missingParentIds')]
    public function testMissingParentReturnsNotFound(?int $parentId): void
    {
        $repository = $this->createMock(DossierRepository::class);
        $repository->expects(null !== $parentId && $parentId > 0 ? $this->once() : $this->never())
            ->method('find')->willReturn(null);
        $repository->expects($this->never())->method('getDossiersEnfants');
        $controller = new DossierAjaxController($repository, $this->dossierService($repository));

        $this->expectException(NotFoundHttpException::class);
        $controller->getDossiersEnfants(new Request([], null === $parentId ? [] : ['parent' => $parentId]));
    }

    public static function missingParentIds(): iterable
    {
        yield 'absent' => [null];
        yield 'zéro' => [0];
        yield 'négatif' => [-1];
        yield 'inexistant' => [42];
    }

    private function dossierService(DossierRepository $repository): DossierService
    {
        return new DossierService(
            $this->createStub(EntityManagerInterface::class),
            $repository,
            $this->createStub(Security::class),
            new PlatauTempsRestant(),
            $this->createStub(TexteApplicable::class),
        );
    }
}
