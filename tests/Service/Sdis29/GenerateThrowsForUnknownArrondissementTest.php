<?php

namespace App\Tests\Service\Sdis29;

use App\Service\Sdis29\NumeroIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class GenerateThrowsForUnknownArrondissementTest extends TestCase
{
    public function testGenerateThrowsForUnknownArrondissement(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $generator = new NumeroIdGenerator($entityManager);

        $this->expectException(\InvalidArgumentException::class);

        $generator->generate('Arrondissement inconnu', '1', null);
    }
}
