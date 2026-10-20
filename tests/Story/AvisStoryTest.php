<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Constant\Avis as AvisId;
use App\Entity\Avis;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[Group('database')]
#[ResetDatabase]
final class AvisStoryTest extends KernelTestCase
{
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
        $this->assertSame(
            "Pas d'avis - à motiver dans la partie Fondement de l'avis",
            $entityManager->find(Avis::class, 6)?->getLibelle(),
        );
        $this->assertSame(3, $entityManager->getRepository(Avis::class)->count([]));
    }
}
