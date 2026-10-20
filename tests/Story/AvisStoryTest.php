<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Constant\Avis as AvisId;
use App\Entity\Avis;
use App\Tests\Factory\AvisFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[Group('database')]
#[ResetDatabase]
final class AvisStoryTest extends KernelTestCase
{
    private const string TEMPORARY_LABEL = 'Avis temporaire pour vérifier le rollback';

    public function testReferenceIdsAndLabelsMatchConstants(): void
    {
        $favorable = AvisStory::favorable();
        $defavorable = AvisStory::defavorable();

        $this->assertSame(AvisId::FAVORABLE, $favorable->getId());
        $this->assertSame('Favorable', $favorable->getLibelle());
        $this->assertSame(AvisId::DEFAVORABLE, $defavorable->getId());
        $this->assertSame('Défavorable', $defavorable->getLibelle());

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        $this->assertSame('Favorable', $entityManager->find(Avis::class, AvisId::FAVORABLE)?->getLibelle());
        $this->assertSame('Défavorable', $entityManager->find(Avis::class, AvisId::DEFAVORABLE)?->getLibelle());
        $this->assertSame(2, $entityManager->getRepository(Avis::class)->count([]));
    }

    public function testCreatesTemporaryAvisInsideTransaction(): int
    {
        $avis = AvisFactory::createOne(['libelle' => self::TEMPORARY_LABEL]);
        $id = $avis->getId();
        $this->assertNotNull($id);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertTrue($entityManager->getConnection()->getNativeConnection()->inTransaction());
        $entityManager->clear();

        $this->assertSame(self::TEMPORARY_LABEL, $entityManager->find(Avis::class, $id)?->getLibelle());
        $this->assertSame(3, $entityManager->getRepository(Avis::class)->count([]));

        return $id;
    }

    #[Depends('testCreatesTemporaryAvisInsideTransaction')]
    public function testPreviousTestWasRolledBackWithoutRecreatingSchema(int $previousId): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $repository = $entityManager->getRepository(Avis::class);

        $this->assertNull($repository->find($previousId));
        $this->assertNull($repository->findOneBy(['libelle' => self::TEMPORARY_LABEL]));
        $this->assertSame(2, $repository->count([]));
        $this->assertSame(AvisId::FAVORABLE, AvisStory::favorable()->getId());
        $this->assertSame(AvisId::DEFAVORABLE, AvisStory::defavorable()->getId());

        // MariaDB keeps AUTO_INCREMENT advances after rollback, unlike a recreated schema.
        $nextAvis = AvisFactory::createOne();
        $this->assertGreaterThan($previousId, $nextAvis->getId());
    }
}
