<?php

namespace App\ValueObject;

use App\Entity\Classement;
use App\Entity\EtablissementActivite;
use App\Entity\EtablissementCategorie;
use App\Entity\Famille;
use App\Entity\IghClasse;
use App\Repository\ClassementRepository;
use App\Repository\EtablissementActiviteRepository;
use App\Repository\EtablissementCategorieRepository;
use App\Repository\FamilleRepository;
use App\Repository\IghClasseRepository;

final class EtablissementTexter
{
    private ?string $typesActivites = null;

    private ?string $categories = null;

    private ?string $familles = null;

    private ?string $classes = null;

    private ?string $classements = null;

    private ?string $useUserCommissions = null;

    private ?string $useUserGroupements = null;

    private ?string $useUserCommune = null;

    public function __construct(private readonly EtablissementActiviteRepository $etablissementActiviteRepository, private readonly EtablissementCategorieRepository $categorieRepository, private readonly FamilleRepository $familleRepository, private readonly IghClasseRepository $classeRepository, private readonly ClassementRepository $classementRepository)
    {
    }

    /**
     * @param ?array<int, string> $typesActivites
     */
    public function setTypesActivites(?array $typesActivites): EtablissementTexter
    {
        if (null === $typesActivites) {
            $this->typesActivites = 'Tous les types';
        } else {
            /** @var array<int, ?EtablissementActivite> $typesEntities */
            $typesEntities = array_map(fn (string $typeId) => $this->etablissementActiviteRepository->find($typeId), $typesActivites);
            /** @var array<int, EtablissementActivite> $typesEntities */
            $typesEntities = array_filter($typesEntities);

            /** @var array<int, ?string> $typesLibelles */
            $typesLibelles = array_map(fn (EtablissementActivite $typeId): ?string => $typeId->getLibelle(), $typesEntities);
            /** @var array<int, string> $typesLibelles */
            $typesLibelles = array_filter($typesLibelles);

            $this->typesActivites = implode('-', $typesLibelles);
        }

        return $this;
    }

    /**
     * @param ?array<int, string> $categories
     */
    public function setCategories(?array $categories): EtablissementTexter
    {
        if (null === $categories) {
            $this->categories = 'Toutes les catégories';
        } else {
            /** @var array<int, ?EtablissementCategorie> $categoriesEntities */
            $categoriesEntities = array_map(fn (string $categorieId) => $this->categorieRepository->find($categorieId), $categories);
            /** @var array<int, EtablissementCategorie> $categoriesEntities */
            $categoriesEntities = array_filter($categoriesEntities);

            /** @var array<int, ?string> $categoriesLibelles */
            $categoriesLibelles = array_map(fn (EtablissementCategorie $categorieId): ?string => $categorieId->getLibelle(), $categoriesEntities);
            /** @var array<int, string> $categoriesLibelles */
            $categoriesLibelles = array_filter($categoriesLibelles);

            $this->categories = implode('-', $categoriesLibelles);
        }

        return $this;
    }

    /**
     * @param ?array<int, string> $familles
     */
    public function setFamilles(?array $familles): EtablissementTexter
    {
        if (null === $familles) {
            $this->familles = 'Toutes les familles';
        } else {
            /** @var array<int, ?Famille> $famillesEntities */
            $famillesEntities = array_map(fn (string $familleId) => $this->familleRepository->find($familleId), $familles);
            /** @var array<int, Famille> $famillesEntities */
            $famillesEntities = array_filter($famillesEntities);

            /** @var array<int, ?string> $famillesLibelles */
            $famillesLibelles = array_map(fn (Famille $familleId): ?string => $familleId->getLibelle(), $famillesEntities);
            /** @var array<int, string> $famillesLibelles */
            $famillesLibelles = array_filter($famillesLibelles);

            $this->familles = implode('-', $famillesLibelles);
        }

        return $this;
    }

    /**
     * @param ?array<int, string> $classes
     */
    public function setClasses(?array $classes): EtablissementTexter
    {
        if (null === $classes) {
            $this->classes = 'Toutes les classes';
        } else {
            /** @var array<int, ?IghClasse> $classesEntities */
            $classesEntities = array_map(fn (string $classeId) => $this->classeRepository->find($classeId), $classes);
            /** @var array<int, IghClasse> $classesEntities */
            $classesEntities = array_filter($classesEntities);

            /** @var array<int, ?string> $classesLibelles */
            $classesLibelles = array_map(fn (IghClasse $classeId): ?string => $classeId->getLibelle(), $classesEntities);
            /** @var array<int, string> $classesLibelles */
            $classesLibelles = array_filter($classesLibelles);

            $this->classes = implode('-', $classesLibelles);
        }

        return $this;
    }

    /**
     * @param ?array<int, string> $classements
     */
    public function setClassements(?array $classements): EtablissementTexter
    {
        if (null === $classements) {
            $this->classements = 'Tous les classements';
        } else {
            /** @var array<int, ?Classement> $classementsEntities */
            $classementsEntities = array_map(fn (string $classementId) => $this->classementRepository->find($classementId), $classements);
            /** @var array<int, Classement> $classementsEntities */
            $classementsEntities = array_filter($classementsEntities);

            /** @var array<int, ?string> $classementsLibelles */
            $classementsLibelles = array_map(fn (Classement $classementId): ?string => $classementId->getLibelle(), $classementsEntities);
            /** @var array<int, string> $classementsLibelles */
            $classementsLibelles = array_filter($classementsLibelles);

            $this->classements = implode('-', $classementsLibelles);
        }

        return $this;
    }

    public function setUseUserCommissions(bool $useUserCommissions): EtablissementTexter
    {
        $this->useUserCommissions = $useUserCommissions ? "Sur les commissions de l'utilisateur" : 'Ignorer les commissions';

        return $this;
    }

    public function setUseUserGroupements(bool $useUserGroupements): EtablissementTexter
    {
        $this->useUserGroupements = $useUserGroupements ? "Sur les groupements de l'utilisateur" : 'Ignorer les groupements';

        return $this;
    }

    public function setUseUserCommune(bool $useUserCommune): EtablissementTexter
    {
        $this->useUserCommune = $useUserCommune ? "Sur la commune de l'utilisateur" : 'Ignorer la commune';

        return $this;
    }

    public function getText(): string
    {
        $items = [
            $this->typesActivites,
            $this->categories,
            $this->familles,
            $this->classes,
            $this->classements,
            $this->useUserCommissions,
            $this->useUserGroupements,
            $this->useUserCommune,
        ];
        $items = array_filter($items);

        return implode(' - ', $items);
    }
}
