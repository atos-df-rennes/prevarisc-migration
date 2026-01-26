<?php

namespace App\Tests\Repository;

use App\Entity\DossierNatureListe;
use App\Repository\DossierNatureListeRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DossierNatureListeRepositoryTest extends KernelTestCase
{
    /** @var DossierNatureListeRepository */
    private $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::$container->get(DossierNatureListeRepository::class);
    }

    /**
     * Test que la méthode findByTypeOrderedByOrdre retourne des résultats pour type 1
     */
    public function testFindByTypeOrderedByOrdreReturnsResults(): void
    {
        // Type 1 = Étude (devrait avoir des natures)
        $natures = $this->repository->findByTypeOrderedByOrdre(1);

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
     * Test que les résultats sont bien triés par ordre puis libellé
     */
    public function testFindByTypeOrderedByOrdreIsSortedCorrectly(): void
    {
        $natures = $this->repository->findByTypeOrderedByOrdre(1);

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
     * Test que la méthode retourne un tableau vide pour un type sans natures
     */
    public function testFindByTypeOrderedByOrdreReturnsEmptyForNonExistentType(): void
    {
        // Type 999 ne devrait pas exister
        $natures = $this->repository->findByTypeOrderedByOrdre(999);

        self::assertIsArray($natures);
        self::assertEmpty($natures, 'Un type inexistant ne devrait avoir aucune nature');
    }

    /**
     * Test que les natures retournées appartiennent bien au type demandé
     */
    public function testFindByTypeOrderedByOrdreReturnsOnlyNaturesOfRequestedType(): void
    {
        $typeId = 2; // Type Visite
        $natures = $this->repository->findByTypeOrderedByOrdre($typeId);

        foreach ($natures as $nature) {
            self::assertNotNull($nature->getType(), 'Chaque nature doit avoir un type');
            self::assertEquals($typeId, $nature->getType()->getId(), 'Toutes les natures doivent appartenir au type demandé');
        }
    }
}
