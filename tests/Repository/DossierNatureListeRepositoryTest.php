<?php

namespace App\Tests\Repository;

use App\Entity\DossierNatureListe;
use App\Repository\DossierNatureListeRepository;
use App\Repository\DossierTypeRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DossierNatureListeRepositoryTest extends KernelTestCase
{
    private DossierNatureListeRepository $repository;

    private DossierTypeRepository $dossierTypeRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(DossierNatureListeRepository::class);
        $this->dossierTypeRepository = self::getContainer()->get(DossierTypeRepository::class);
    }

    /**
     * Test que la méthode findByTypeOrderedByOrdre retourne des résultats pour type 1.
     */
    public function testFindByTypeOrderedByOrdreReturnsResults(): void
    {
        // Type 1 = Étude (devrait avoir des natures)
        $type = $this->dossierTypeRepository->find(1);

        self::assertNotNull($type, 'Le type 1 devrait exister en base de données');

        $natures = $this->repository->findByTypeOrderedByOrdre($type);

        self::assertIsArray($natures);
        self::assertNotEmpty($natures, 'Le type 1 devrait avoir au moins une nature');

        // Vérifier que ce sont bien des DossierNatureListe
        foreach ($natures as $nature) {
            self::assertInstanceOf(DossierNatureListe::class, $nature);
            self::assertNotNull($nature->getId());
            self::assertNotNull($nature->getLibelle());
        }
    }

    /**
     * Test que les résultats sont bien triés par ordre puis libellé.
     */
    public function testFindByTypeOrderedByOrdreIsSortedCorrectly(): void
    {
        $type = $this->dossierTypeRepository->find(1);

        self::assertNotNull($type, 'Le type 1 devrait exister en base de données');

        $natures = $this->repository->findByTypeOrderedByOrdre($type);

        if (count($natures) < 2) {
            self::markTestSkipped('Pas assez de natures pour tester le tri');
        }

        // Vérifier que le tri par ordre est respecté
        $previousOrdre = null;
        foreach ($natures as $nature) {
            $ordre = $nature->getOrdre();
            if (null !== $previousOrdre && null !== $ordre) {
                self::assertGreaterThanOrEqual($previousOrdre, $ordre, 'Les natures doivent être triées par ordre croissant');
            }

            $previousOrdre = $ordre;
        }
    }

    /**
     * Test que les natures retournées appartiennent bien au type demandé.
     */
    public function testFindByTypeOrderedByOrdreReturnsOnlyNaturesOfRequestedType(): void
    {
        $type = $this->dossierTypeRepository->find(2); // Type 2 = Visite

        if (!$type) {
            self::markTestSkipped('Le type 2 n\'existe pas en base de données');
        }

        $natures = $this->repository->findByTypeOrderedByOrdre($type);

        foreach ($natures as $nature) {
            self::assertNotNull($nature->getType(), 'Chaque nature doit avoir un type');
            self::assertEquals($type->getId(), $nature->getType()->getId(), 'Toutes les natures doivent appartenir au type demandé');
        }
    }
}
