<?php

namespace App\Tests\Service;

use App\Entity\UtilisateurInformations;
use App\Repository\CommissionRepository;
use App\Repository\CommuneRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;
use App\Repository\GroupementRepository;
use App\Service\Contact;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ContactTest extends TestCase
{
    /** @var UtilisateurInformations */
    private $contact;

    /** @var EtablissementRepository|MockObject */
    private $etablissementRepository;

    /** @var DossierRepository|MockObject */
    private $dossierRepository;

    /** @var GroupementRepository|MockObject */
    private $groupementRepository;

    /** @var CommissionRepository|MockObject */
    private $commissionRepository;

    /** @var CommuneRepository|MockObject */
    private $communeRepository;

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

        self::assertFalse($this->buildService()->isUsed($this->contact, Contact::COMMISSION));
    }

    public function testContactPartageAvecUneAutreEntite(): void
    {
        $this->commissionRepository->method('findNumberByContacts')->willReturn(1);
        $this->etablissementRepository->method('findNumberByContacts')->willReturn(1);
        $this->dossierRepository->method('findNumberByContacts')->willReturn(0);
        $this->groupementRepository->method('findNumberByContacts')->willReturn(0);
        $this->communeRepository->method('findNumberByContacts')->willReturn(0);

        self::assertTrue($this->buildService()->isUsed($this->contact, Contact::COMMISSION));
    }

    public function testContactNonUtilise(): void
    {
        $this->commissionRepository->method('findNumberByContacts')->willReturn(0);
        $this->etablissementRepository->method('findNumberByContacts')->willReturn(0);
        $this->dossierRepository->method('findNumberByContacts')->willReturn(0);
        $this->groupementRepository->method('findNumberByContacts')->willReturn(0);
        $this->communeRepository->method('findNumberByContacts')->willReturn(0);

        self::assertFalse($this->buildService()->isUsed($this->contact, Contact::COMMISSION));
    }

    public function testContactUtiliseSeulementParUneAutreEntite(): void
    {
        $this->commissionRepository->method('findNumberByContacts')->willReturn(0);
        $this->etablissementRepository->method('findNumberByContacts')->willReturn(1);
        $this->dossierRepository->method('findNumberByContacts')->willReturn(0);
        $this->groupementRepository->method('findNumberByContacts')->willReturn(0);
        $this->communeRepository->method('findNumberByContacts')->willReturn(0);

        self::assertTrue($this->buildService()->isUsed($this->contact, Contact::COMMISSION));
    }

    public function testContactUtiliseParToutesLesEntites(): void
    {
        $this->commissionRepository->method('findNumberByContacts')->willReturn(1);
        $this->etablissementRepository->method('findNumberByContacts')->willReturn(1);
        $this->dossierRepository->method('findNumberByContacts')->willReturn(1);
        $this->groupementRepository->method('findNumberByContacts')->willReturn(1);
        $this->communeRepository->method('findNumberByContacts')->willReturn(1);

        self::assertTrue($this->buildService()->isUsed($this->contact, Contact::COMMISSION));
    }

    /**
     * Vérifie que le minimalValidCount est correct pour chaque type d'entité.
     * Un contact lié uniquement à l'entité courante doit retourner false.
     *
     * @dataProvider provideElementLieAvecSonRepository
     */
    public function testContactSeulementSurEntiteCouranteRetourneFalsePourChaqueType(
        string $elementLie,
        string $repositoryProperty
    ): void {
        foreach (['etablissementRepository', 'dossierRepository', 'groupementRepository', 'commissionRepository', 'communeRepository'] as $repo) {
            $count = $repo === $repositoryProperty ? 1 : 0;
            $this->{$repo}->method('findNumberByContacts')->willReturn($count);
        }

        self::assertFalse($this->buildService()->isUsed($this->contact, $elementLie));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function provideElementLieAvecSonRepository(): array
    {
        return [
            'etablissement' => [Contact::ETABLISSEMENT, 'etablissementRepository'],
            'dossier' => [Contact::DOSSIER, 'dossierRepository'],
            'groupement' => [Contact::GROUPEMENT, 'groupementRepository'],
            'commission' => [Contact::COMMISSION, 'commissionRepository'],
            'commune' => [Contact::COMMUNE, 'communeRepository'],
        ];
    }
}
