<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Repository\PrescriptionTypeRepository;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('functional')]
final class PrescriptionTypeRepositoryTest extends KernelTestCase
{
    private PrescriptionTypeRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(PrescriptionTypeRepository::class);
    }

    public function testFindByClassementAvecCategorieFiltreLesResultats(): void
    {
        $tous = $this->repository->findAll();

        if ([] === $tous) {
            $this->markTestSkipped('Aucune prescription type en base de données.');
        }

        $premiereCategorie = $tous[0]->getCategorie();

        if (null === $premiereCategorie) {
            $this->markTestSkipped("La première prescription type n'a pas de catégorie.");
        }

        $filtres = $this->repository->findByClassement((int) $premiereCategorie, 0, 0);

        $this->assertIsArray($filtres);

        foreach ($filtres as $prescriptionType) {
            $this->assertSame($premiereCategorie, $prescriptionType->getCategorie());
        }
    }

    public function testFindByMotsClesAvecTableauVideRetourneTableauVide(): void
    {
        $results = $this->repository->findByMotsCles([]);

        $this->assertSame([], $results);
    }

    public function testFindByMotsClesAvecMotInexistantRetourneTableauVide(): void
    {
        $results = $this->repository->findByMotsCles(['xyzinexistant12345']);

        $this->assertIsArray($results);
        $this->assertSame([], $results);
    }

    public function testFindByMotsClesRechercheParLibelle(): void
    {
        $tous = $this->repository->findAll();

        if ([] === $tous) {
            $this->markTestSkipped('Aucune prescription type en base de données.');
        }

        $premierLibelle = $tous[0]->getLibelle();

        if (null === $premierLibelle || '' === $premierLibelle) {
            $this->markTestSkipped("La première prescription type n'a pas de libellé.");
        }

        $premierMot = explode(' ', trim($premierLibelle))[0];
        $results = $this->repository->findByMotsCles([$premierMot]);

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }
}
