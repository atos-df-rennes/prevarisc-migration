<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Entity\Groupe;
use App\Entity\Privileges;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Story;

final class GroupeDefautStory extends Story
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function build(): void
    {
        PrivilegesStory::load();
        $this->entityManager->getConnection()->insert('groupe', [
            'ID_GROUPE' => 1,
            'LIBELLE_GROUPE' => 'Groupe par défaut',
            'DESC_GROUPE' => 'Ceci est le groupe qui contient les nouveaux utilisateurs et les utilisateurs dont les groupes ont été supprimés.',
        ]);

        $groupe = $this->entityManager->find(Groupe::class, 1);
        foreach ($this->entityManager->getRepository(Privileges::class)->findBy([
            'id' => [17, 19, 21, 23, 25, 27, 52, 54, 56, 58, 63, 64],
        ]) as $privilege) {
            $groupe->addPrivilege($privilege);
        }

        $this->entityManager->flush();
        $this->addState('groupe', $groupe);
    }

    public static function groupe(): Groupe
    {
        return self::get('groupe');
    }
}
