<?php

declare(strict_types=1);

namespace App\Tests\Service\Commission;

use App\Entity\Commission;
use App\Service\Commission\CommissionDocumentCrService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Teste la gestion du document type (Compte Rendu) d'une commission.
 */
final class CommissionDocumentCrServiceTest extends TestCase
{
    private string $tmpDir;

    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManager;

    private CommissionDocumentCrService $service;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/commission_cr_test_'.uniqid().'/';
        mkdir($this->tmpDir, 0777, true);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $this->service = new CommissionDocumentCrService(
            $this->tmpDir,
            $this->entityManager,
            new Filesystem(),
            $logger
        );
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->tmpDir);
    }

    public function testUploadFichierOdtValide(): void
    {
        $tmpFile = $this->tmpDir.'source.odt';
        file_put_contents($tmpFile, 'contenu odt');

        $uploadedFile = new UploadedFile($tmpFile, 'compte_rendu.odt', 'application/vnd.oasis.opendocument.text', null, true);

        $commission = new Commission();

        $this->entityManager->expects($this->once())->method('flush');

        $this->service->upload($commission, $uploadedFile);

        $this->assertSame('compte_rendu.odt', $commission->getDocumentCr());
        $this->assertFileExists($this->tmpDir.'compte_rendu.odt');
    }

    public function testUploadRemplaceAncienFichier(): void
    {
        $ancienFichier = $this->tmpDir.'ancien.odt';
        file_put_contents($ancienFichier, 'ancien contenu');

        $commission = new Commission();
        $commission->setDocumentCr('ancien.odt');

        $tmpFile = $this->tmpDir.'source_nouveau.odt';
        file_put_contents($tmpFile, 'nouveau contenu');

        $uploadedFile = new UploadedFile($tmpFile, 'nouveau.odt', 'application/vnd.oasis.opendocument.text', null, true);

        $this->entityManager->expects($this->once())->method('flush');

        $this->service->upload($commission, $uploadedFile);

        $this->assertSame('nouveau.odt', $commission->getDocumentCr());
        $this->assertFileDoesNotExist($ancienFichier);
        $this->assertFileExists($this->tmpDir.'nouveau.odt');
    }

    public function testDeleteSupprimeFichierEtVideBdd(): void
    {
        $fichier = $this->tmpDir.'compte_rendu.odt';
        file_put_contents($fichier, 'contenu');

        $commission = new Commission();
        $commission->setDocumentCr('compte_rendu.odt');

        $this->entityManager->expects($this->once())->method('flush');

        $this->service->delete($commission);

        $this->assertNull($commission->getDocumentCr());
        $this->assertFileDoesNotExist($fichier);
    }

    public function testDeleteSansDocumentNeFaitRien(): void
    {
        $commission = new Commission();

        $this->entityManager->expects($this->never())->method('flush');

        $this->service->delete($commission);

        $this->assertNull($commission->getDocumentCr());
    }

    public function testDeleteFichierAbsentNeLeveAucuneException(): void
    {
        $commission = new Commission();
        $commission->setDocumentCr('fichier_inexistant.odt');

        $this->entityManager->expects($this->once())->method('flush');

        // Ne doit pas lever d'exception même si le fichier n'existe pas
        $this->service->delete($commission);

        $this->assertNull($commission->getDocumentCr());
    }

    public function testDocumentCrExistsRetourneTrueSiFichierPresent(): void
    {
        $fichier = $this->tmpDir.'compte_rendu.odt';
        file_put_contents($fichier, 'contenu');

        $commission = new Commission();
        $commission->setDocumentCr('compte_rendu.odt');

        $this->assertTrue($this->service->documentCrExists($commission));
    }

    public function testDocumentCrExistsRetourneFalseSiAucunDocument(): void
    {
        $commission = new Commission();

        $this->assertFalse($this->service->documentCrExists($commission));
    }

    public function testDocumentCrExistsRetourneFalseSiFichierAbsent(): void
    {
        $commission = new Commission();
        $commission->setDocumentCr('fichier_absent.odt');

        $this->assertFalse($this->service->documentCrExists($commission));
    }

    /**
     * Régression : une chaîne vide résiduelle en base (bug historique, cf. migration
     * Version20260909092425) doit être traitée comme "aucun document", même lorsqu'elle
     * est présente directement sur la propriété (hydratation Doctrine, qui ne passe pas
     * par le setter).
     */
    public function testDocumentCrExistsRetourneFalseSiChaineVideEnBase(): void
    {
        $commission = new Commission();
        $commission->setDocumentCr('');

        $this->assertFalse($this->service->documentCrExists($commission));
    }
}
