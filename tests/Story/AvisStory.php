<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Constant\Avis as AvisId;
use App\Entity\Avis;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Story;

final class AvisStory extends Story
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function build(): void
    {
        $connection = $this->entityManager->getConnection();
        foreach ([
            AvisId::FAVORABLE => 'Favorable',
            AvisId::DEFAVORABLE => 'Défavorable',
            6 => "Pas d'avis - à motiver dans la partie Fondement de l'avis",
        ] as $id => $libelle) {
            $connection->insert('avis', ['ID_AVIS' => $id, 'LIBELLE_AVIS' => $libelle]);
        }

        $repository = $this->entityManager->getRepository(Avis::class);
        $this->addState('favorable', $repository->find(AvisId::FAVORABLE));
        $this->addState('defavorable', $repository->find(AvisId::DEFAVORABLE));
    }

    public static function favorable(): Avis
    {
        return self::get('favorable');
    }

    public static function defavorable(): Avis
    {
        return self::get('defavorable');
    }
}
