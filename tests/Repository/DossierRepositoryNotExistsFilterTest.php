<?php

namespace App\Tests\Repository;

use App\Repository\DossierRepository;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests fonctionnels pour le filtrage NOT EXISTS (dossiers liés) dans DossierRepository.
 * Couvre les méthodes findLeveePresc, findAbsenceQuorum et findNpsp qui excluent
 * les dossiers possédant un dossier lié d'une nature ou d'un type donné.
 */
#[Group('functional')]
class DossierRepositoryNotExistsFilterTest extends KernelTestCase
{
    private DossierRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(DossierRepository::class);
    }

    /**
     * findLeveePresc retourne un tableau (éventuellement vide).
     * Valide que la clause NOT EXISTS par nature s'exécute sans erreur SQL.
     */
    public function testFindLeveePrescReturnsArray(): void
    {
        $result = $this->repository->findLeveePresc();

        self::assertIsArray($result);
    }

    /**
     * findAbsenceQuorum retourne un tableau (éventuellement vide).
     * Valide que la clause NOT EXISTS par type s'exécute sans erreur SQL.
     */
    public function testFindAbsenceQuorumReturnsArray(): void
    {
        $result = $this->repository->findAbsenceQuorum();

        self::assertIsArray($result);
    }

    /**
     * findNpsp retourne un tableau (éventuellement vide).
     * Valide que la clause NOT EXISTS par type s'exécute sans erreur SQL (ID_DOSSIER2 sens inverse).
     */
    public function testFindNpspReturnsArray(): void
    {
        $result = $this->repository->findNpsp();

        self::assertIsArray($result);
    }

    /**
     * Les dossiers retournés par findLeveePresc ont bien un ID_DOSSIER.
     */
    public function testFindLeveePrescResultsHaveIdDossier(): void
    {
        $result = $this->repository->findLeveePresc();

        if ([] === $result) {
            $this->markTestSkipped('Aucun dossier avec levée de prescription en base.');
        }

        foreach ($result as $dto) {
            self::assertNotNull($dto->ID_DOSSIER);
        }
    }

    /**
     * Les dossiers retournés par findAbsenceQuorum ont bien un ID_DOSSIER.
     */
    public function testFindAbsenceQuorumResultsHaveIdDossier(): void
    {
        $result = $this->repository->findAbsenceQuorum();

        if ([] === $result) {
            $this->markTestSkipped('Aucun dossier avec absence de quorum en base.');
        }

        foreach ($result as $dto) {
            self::assertNotNull($dto->ID_DOSSIER);
        }
    }

    /**
     * Les dossiers retournés par findNpsp ont bien un ID_DOSSIER.
     */
    public function testFindNpspResultsHaveIdDossier(): void
    {
        $result = $this->repository->findNpsp();

        if ([] === $result) {
            $this->markTestSkipped('Aucun dossier NPSP en base.');
        }

        foreach ($result as $dto) {
            self::assertNotNull($dto->ID_DOSSIER);
        }
    }
}
