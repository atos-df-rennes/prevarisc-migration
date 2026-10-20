<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Entity\Groupe;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\UtilisateurFactory;
use Zenstruck\Foundry\Story;

final class NavigationAdminStory extends Story
{
    public const string USERNAME = 'admin';

    public function __construct(private readonly PrivilegesRepository $privilegesRepository)
    {
    }

    public function build(): void
    {
        GroupeDefautStory::load();
        $groupe = GroupeFactory::createOne([
            'libelle' => 'Administrateurs (tests)',
            'privileges' => $this->privilegesRepository->findBy([], ['id' => 'ASC'], $this->privilegesRepository->count([])),
        ]);
        $this->addState('groupe', $groupe);
        $this->addState('admin', UtilisateurFactory::createOne([
            'username' => self::USERNAME,
            'groupe' => $groupe,
        ]));
    }

    public static function admin(): Utilisateur
    {
        return self::get('admin');
    }

    public static function groupe(): Groupe
    {
        return self::get('groupe');
    }
}
