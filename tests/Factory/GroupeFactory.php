<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Groupe;
use App\Entity\Privileges;
use App\Repository\PrivilegesRepository;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Groupe>
 */
final class GroupeFactory extends PersistentObjectFactory
{
    public function __construct(private readonly PrivilegesRepository $privilegesRepository)
    {
    }

    public static function class(): string
    {
        return Groupe::class;
    }

    public function withPrivileges(int ...$ids): self
    {
        return $this->afterInstantiate(function (Groupe $groupe) use ($ids): void {
            $privileges = $this->privilegesRepository->findBy(['id' => $ids]);
            $missingIds = array_diff($ids, array_map(static fn (Privileges $privilege): ?int => $privilege->getId(), $privileges));
            if ([] !== $missingIds) {
                throw new \InvalidArgumentException('Privileges introuvables : '.implode(', ', $missingIds));
            }

            foreach ($privileges as $privilege) {
                $groupe->addPrivilege($privilege);
            }
        });
    }

    protected function defaults(): array
    {
        return [
            'libelle' => self::faker()->unique()->words(3, true),
        ];
    }
}
