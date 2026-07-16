<?php

namespace App\Tests\Service\Commission;

use App\Entity\CommissionMembre;
use App\Enum\CommissionMembreCourrierType;
use App\Service\Commission\CommissionMembreCourrierService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Teste la gestion des courriers types des membres de commission.
 */
final class CommissionMembreCourrierServiceTest extends TestCase
{
    private string $tmpDir;

    /** @var EntityManagerInterface|MockObject */
    private MockObject $entityManager;

    /** @var LoggerInterface|MockObject */
    private MockObject $logger;

    private CommissionMembreCourrierService $service;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/commission_courrier_test_'.uniqid().'/';
        mkdir($this->tmpDir, 0777, true);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new CommissionMembreCourrierService(
            $this->tmpDir,
            $this->entityManager,
            new Filesystem(),
            $this->logger
        );
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tmpDir);
    }

    /**
     * @dataProvider uploadTypesProvider
     */
    public function testUploadCourrierValide(string $type, string $getter): void
    {
        $tmpFile = $this->tmpDir.'source.odt';
        file_put_contents($tmpFile, 'contenu odt');

        $uploadedFile = new UploadedFile($tmpFile, 'courrier.odt', 'application/vnd.oasis.opendocument.text', null, true);

        $membre = $this->createMembreWithId(42);

        $this->entityManager->expects(self::once())->method('flush');

        $this->service->upload($membre, $type, $uploadedFile);

        self::assertSame('courrier.odt', $membre->$getter());
        self::assertFileExists($this->tmpDir.'42'.$type.'_courrier.odt');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function uploadTypesProvider(): array
    {
        return [
            'convocation visite' => [CommissionMembreCourrierType::CONVOCATIONVISITE, 'getCourrierConvocationVisite'],
            'convocation salle' => [CommissionMembreCourrierType::CONVOCATIONSALLE, 'getCourrierConvocationSalle'],
            'ordre du jour' => [CommissionMembreCourrierType::ODJ, 'getCourrierOrdreDuJour'],
            'pv' => [CommissionMembreCourrierType::PV, 'getCourrierProcesVerbal'],
        ];
    }

    public function testUploadRemplaceAncienCourrier(): void
    {
        $ancienFichier = $this->tmpDir.'42CONVOCATIONVISITE_ancien.odt';
        file_put_contents($ancienFichier, 'ancien contenu');

        $membre = $this->createMembreWithId(42);
        $membre->setCourrierConvocationVisite('ancien.odt');

        $tmpFile = $this->tmpDir.'source_nouveau.odt';
        file_put_contents($tmpFile, 'nouveau contenu');

        $uploadedFile = new UploadedFile($tmpFile, 'nouveau.odt', 'application/vnd.oasis.opendocument.text', null, true);

        $this->entityManager->expects(self::once())->method('flush');

        $this->service->upload($membre, CommissionMembreCourrierType::CONVOCATIONVISITE, $uploadedFile);

        self::assertSame('nouveau.odt', $membre->getCourrierConvocationVisite());
        self::assertFileDoesNotExist($ancienFichier);
        self::assertFileExists($this->tmpDir.'42CONVOCATIONVISITE_nouveau.odt');
    }

    public function testDeleteSupprimeFichierEtVideBdd(): void
    {
        $fichier = $this->tmpDir.'42ODJ_odj.odt';
        file_put_contents($fichier, 'contenu');

        $membre = $this->createMembreWithId(42);
        $membre->setCourrierOrdreDuJour('odj.odt');

        $this->entityManager->expects(self::once())->method('flush');

        $this->service->delete($membre, CommissionMembreCourrierType::ODJ);

        self::assertNull($membre->getCourrierOrdreDuJour());
        self::assertFileDoesNotExist($fichier);
    }

    public function testDeleteSansCourrierNeFaitRien(): void
    {
        $membre = $this->createMembreWithId(42);

        $this->entityManager->expects(self::never())->method('flush');

        $this->service->delete($membre, CommissionMembreCourrierType::CONVOCATIONSALLE);

        self::assertNull($membre->getCourrierConvocationSalle());
    }

    public function testDeleteFichierAbsentNeLeveAucuneException(): void
    {
        $membre = $this->createMembreWithId(42);
        $membre->setCourrierProcesVerbal('fichier_inexistant.odt');

        $this->entityManager->expects(self::once())->method('flush');

        $this->service->delete($membre, CommissionMembreCourrierType::PV);

        self::assertNull($membre->getCourrierProcesVerbal());
    }

    public function testUploadTypeInvalideLeveException(): void
    {
        $tmpFile = $this->tmpDir.'source.odt';
        file_put_contents($tmpFile, 'contenu');
        $uploadedFile = new UploadedFile($tmpFile, 'courrier.odt', 'application/vnd.oasis.opendocument.text', null, true);

        $membre = $this->createMembreWithId(42);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->upload($membre, 'TYPEINVALIDE', $uploadedFile);
    }

    public function testDeleteTypeInvalideLeveException(): void
    {
        $membre = $this->createMembreWithId(42);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->delete($membre, 'TYPEINVALIDE');
    }

    public function testCourrierExistsRetourneTrueSiFichierPresent(): void
    {
        $fichier = $this->tmpDir.'42ODJ_odj.odt';
        file_put_contents($fichier, 'contenu');

        $membre = $this->createMembreWithId(42);
        $membre->setCourrierOrdreDuJour('odj.odt');

        self::assertTrue($this->service->courrierExists($membre, CommissionMembreCourrierType::ODJ));
    }

    public function testCourrierExistsRetourneFalseSiAucunCourrier(): void
    {
        $membre = $this->createMembreWithId(42);

        self::assertFalse($this->service->courrierExists($membre, CommissionMembreCourrierType::CONVOCATIONVISITE));
    }

    public function testCourrierExistsRetourneFalseSiFichierAbsent(): void
    {
        $membre = $this->createMembreWithId(42);
        $membre->setCourrierProcesVerbal('fichier_absent.odt');

        self::assertFalse($this->service->courrierExists($membre, CommissionMembreCourrierType::PV));
    }

    public function testCourrierExistsTypeInvalideLeveException(): void
    {
        $membre = $this->createMembreWithId(42);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->courrierExists($membre, 'TYPEINVALIDE');
    }

    /**
     * Crée un CommissionMembre avec un ID forcé via réflexion.
     */
    private function createMembreWithId(int $id): CommissionMembre
    {
        $membre = new CommissionMembre();
        $reflection = new \ReflectionClass($membre);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($membre, $id);

        return $membre;
    }
}
