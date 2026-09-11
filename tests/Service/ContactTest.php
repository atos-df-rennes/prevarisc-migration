<?php

declare(strict_types=1);

namespace App\Tests\Service;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use App\Entity\UtilisateurInformations;
use App\Repository\CommissionRepository;
use App\Repository\CommuneRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;
use App\Repository\GroupementRepository;
use App\Service\Contact;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class ContactTest extends TestCase
{
    private UtilisateurInformations $contact;

    /** @var MockObject&EtablissementRepository */
    private MockObject $etablissementRepository;

    /** @var MockObject&DossierRepository */
    private MockObject $dossierRepository;

    /** @var MockObject&GroupementRepository */
    private MockObject $groupementRepository;

    /** @var MockObject&CommissionRepository */
    private MockObject $commissionRepository;

    /** @var MockObject&CommuneRepository */
    private MockObject $communeRepository;

    protected function setUp(): void
    {
        $this->contact = new UtilisateurInformations();
        $this->etablissementRepository = $this->createMock(EtablissementRepository::class);
        $this->dossierRepository = $this->createMock(DossierRepository::class);
        $this->groupementRepository = $this->createMock(GroupementRepository::class);
        $this->commissionRepository = $this->createMock(CommissionRepository::class);
        $this->communeRepository = $this->createMock(CommuneRepository::class);
    }

    private function buildService(): Contact
    {
        return new Contact(
            $this->etablissementRepository,
            $this->dossierRepository,
            $this->groupementRepository,
            $this->commissionRepository,
            $this->communeRepository
        );
    }

    public function testContactUtiliseSeulementParEntiteCourante(): void
    {
        $this->commissionRepository->method('findNumberByContacts')->willReturn(1);
        $this->etablissementRepository->method('findNumberByContacts')->willReturn(0);
        $this->dossierRepository->method('findNumberByContacts')->willReturn(0);
        $this->groupementRepository->method('findNumberByContacts')->willReturn(0);
        $this->communeRepository->method('findNumberByContacts')->willReturn(0);

        $this->assertFalse($this->buildService()->isUsed($this->contact, Contact::COMMISSION));
    }

    public function testContactPartageAvecUneAutreEntite(): void
    {
        $this->commissionRepository->method('findNumberByContacts')->willReturn(1);
        $this->etablissementRepository->method('findNumberByContacts')->willReturn(1);
        $this->dossierRepository->method('findNumberByContacts')->willReturn(0);
        $this->groupementRepository->method('findNumberByContacts')->willReturn(0);
        $this->communeRepository->method('findNumberByContacts')->willReturn(0);

        $this->assertTrue($this->buildService()->isUsed($this->contact, Contact::COMMISSION));
    }

    public function testContactNonUtilise(): void
    {
        $this->commissionRepository->method('findNumberByContacts')->willReturn(0);
        $this->etablissementRepository->method('findNumberByContacts')->willReturn(0);
        $this->dossierRepository->method('findNumberByContacts')->willReturn(0);
        $this->groupementRepository->method('findNumberByContacts')->willReturn(0);
        $this->communeRepository->method('findNumberByContacts')->willReturn(0);

        $this->assertFalse($this->buildService()->isUsed($this->contact, Contact::COMMISSION));
    }

    public function testContactUtiliseSeulementParUneAutreEntite(): void
    {
        $this->commissionRepository->method('findNumberByContacts')->willReturn(0);
        $this->etablissementRepository->method('findNumberByContacts')->willReturn(1);
        $this->dossierRepository->method('findNumberByContacts')->willReturn(0);
        $this->groupementRepository->method('findNumberByContacts')->willReturn(0);
        $this->communeRepository->method('findNumberByContacts')->willReturn(0);

        $this->assertTrue($this->buildService()->isUsed($this->contact, Contact::COMMISSION));
    }

    public function testContactUtiliseParToutesLesEntites(): void
    {
        $this->commissionRepository->method('findNumberByContacts')->willReturn(1);
        $this->etablissementRepository->method('findNumberByContacts')->willReturn(1);
        $this->dossierRepository->method('findNumberByContacts')->willReturn(1);
        $this->groupementRepository->method('findNumberByContacts')->willReturn(1);
        $this->communeRepository->method('findNumberByContacts')->willReturn(1);

        $this->assertTrue($this->buildService()->isUsed($this->contact, Contact::COMMISSION));
    }

    /**
     * Vérifie que le minimalValidCount est correct pour chaque type d'entité.
     * Un contact lié uniquement à l'entité courante doit retourner false.
     */
    #[DataProvider('provideElementLieAvecSonRepository')]
    public function testContactSeulementSurEntiteCouranteRetourneFalsePourChaqueType(
        string $elementLie,
        string $repositoryProperty
    ): void {
        foreach (['etablissementRepository', 'dossierRepository', 'groupementRepository', 'commissionRepository', 'communeRepository'] as $repo) {
            $count = $repo === $repositoryProperty ? 1 : 0;
            $this->{$repo}->method('findNumberByContacts')->willReturn($count);
        }

        $this->assertFalse($this->buildService()->isUsed($this->contact, $elementLie));
    }

    /**
     * @return \Iterator<string, array{string, string}>
     */
    public static function provideElementLieAvecSonRepository(): \Iterator
    {
        yield 'etablissement' => [Contact::ETABLISSEMENT, 'etablissementRepository'];
        yield 'dossier' => [Contact::DOSSIER, 'dossierRepository'];
        yield 'groupement' => [Contact::GROUPEMENT, 'groupementRepository'];
        yield 'commission' => [Contact::COMMISSION, 'commissionRepository'];
        yield 'commune' => [Contact::COMMUNE, 'communeRepository'];
    }
}
