<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Constant\Fonction as FonctionId;
use App\Entity\UtilisateurInformations;
use App\Repository\FonctionRepository;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<UtilisateurInformations>
 */
final class UtilisateurInformationsFactory extends PersistentObjectFactory
{
    public function __construct(private readonly FonctionRepository $fonctionRepository)
    {
    }

    public static function class(): string
    {
        return UtilisateurInformations::class;
    }

    public function preventionniste(): self
    {
        return $this->with(fn (): array => [
            'fonction' => $this->fonctionRepository->find(FonctionId::PREVENTIONNISTE),
        ]);
    }

    protected function defaults(): array
    {
        return [
            'nom' => self::faker()->lastName(),
            'prenom' => self::faker()->firstName(),
        ];
    }
}
