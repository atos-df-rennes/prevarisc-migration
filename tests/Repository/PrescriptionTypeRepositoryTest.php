<?php

namespace App\Tests\Repository;

use App\Repository\PrescriptionTypeRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group functional
 */
class PrescriptionTypeRepositoryTest extends KernelTestCase
{
    /** @var PrescriptionTypeRepository */
    private $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::$container->get(PrescriptionTypeRepository::class);
    }

    public function testFindByClassementSansFiltrRetourneToutesLesPrescriptions(): void
    {
        $results = $this->repository->findByClassement(0, 0, 0);

        self::assertIsArray($results);
    }

    public function testFindByClassementAvecCategorieFiltreLesResultats(): void
    {
        $tous = $this->repository->findByClassement(0, 0, 0);

        if ([] === $tous) {
            $this->markTestSkipped('Aucune prescription type en base de données.');
        }

        $premiereCategorie = $tous[0]->getCategorie();

        if (null === $premiereCategorie) {
            $this->markTestSkipped('La première prescription type n\'a pas de catégorie.');
        }

        $filtres = $this->repository->findByClassement((int) $premiereCategorie, 0, 0);

        self::assertIsArray($filtres);

        foreach ($filtres as $prescriptionType) {
            self::assertSame($premiereCategorie, $prescriptionType->getCategorie());
        }
    }

    public function testFindByMotsClesAvecTableauVideRetourneTableauVide(): void
    {
        $results = $this->repository->findByMotsCles([]);

        self::assertSame([], $results);
    }

    public function testFindByMotsClesAvecMotInexistantRetourneTableauVide(): void
    {
        $results = $this->repository->findByMotsCles(['xyzinexistant12345']);

        self::assertIsArray($results);
        self::assertSame([], $results);
    }

    public function testFindByMotsClesRechercheParLibelle(): void
    {
        $tous = $this->repository->findByClassement(0, 0, 0);

        if ([] === $tous) {
            $this->markTestSkipped('Aucune prescription type en base de données.');
        }

        $premierLibelle = $tous[0]->getLibelle();

        if (null === $premierLibelle || '' === $premierLibelle) {
            $this->markTestSkipped('La première prescription type n\'a pas de libellé.');
        }

        $premierMot = explode(' ', trim($premierLibelle))[0];
        $results = $this->repository->findByMotsCles([$premierMot]);

        self::assertIsArray($results);
        self::assertNotEmpty($results);
    }
}
