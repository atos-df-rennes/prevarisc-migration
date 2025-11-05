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
    /** @var ?string */
    private $typesActivites;

    /** @var ?string */
    private $categories;

    /** @var ?string */
    private $familles;

    /** @var ?string */
    private $classes;

    /** @var ?string */
    private $classements;

    /** @var ?string */
    private $useUserCommissions;

    /** @var ?string */
    private $useUserGroupements;

    /** @var ?string */
    private $useUserCommune;

    /** @var EtablissementActiviteRepository */
    private $etablissementActiviteRepository;

    /** @var EtablissementCategorieRepository */
    private $categorieRepository;

    /** @var FamilleRepository */
    private $familleRepository;

    /** @var IghClasseRepository */
    private $classeRepository;

    /** @var ClassementRepository */
    private $classementRepository;

    public function __construct(
        EtablissementActiviteRepository $etablissementActiviteRepository,
        EtablissementCategorieRepository $categorieRepository,
        FamilleRepository $familleRepository,
        IghClasseRepository $classeRepository,
        ClassementRepository $classementRepository
    ) {
        $this->etablissementActiviteRepository = $etablissementActiviteRepository;
        $this->categorieRepository = $categorieRepository;
        $this->familleRepository = $familleRepository;
        $this->classeRepository = $classeRepository;
        $this->classementRepository = $classementRepository;
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
            $typesEntities = array_map(function (string $typeId) {
                return $this->etablissementActiviteRepository->find($typeId);
            }, $typesActivites);
            /** @var array<int, EtablissementActivite> $typesEntities */
            $typesEntities = array_filter($typesEntities);

            /** @var array<int, ?string> $typesLibelles */
            $typesLibelles = array_map(function (EtablissementActivite $typeId): ?string {
                return $typeId->getLibelle();
            }, $typesEntities);
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
            $categoriesEntities = array_map(function (string $categorieId) {
                return $this->categorieRepository->find($categorieId);
            }, $categories);
            /** @var array<int, EtablissementCategorie> $categoriesEntities */
            $categoriesEntities = array_filter($categoriesEntities);

            /** @var array<int, ?string> $categoriesLibelles */
            $categoriesLibelles = array_map(function (EtablissementCategorie $categorieId): ?string {
                return $categorieId->getLibelle();
            }, $categoriesEntities);
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
            $famillesEntities = array_map(function (string $familleId) {
                return $this->familleRepository->find($familleId);
            }, $familles);
            /** @var array<int, Famille> $famillesEntities */
            $famillesEntities = array_filter($famillesEntities);

            /** @var array<int, ?string> $famillesLibelles */
            $famillesLibelles = array_map(function (Famille $familleId): ?string {
                return $familleId->getLibelle();
            }, $famillesEntities);
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
            $classesEntities = array_map(function (string $classeId) {
                return $this->classeRepository->find($classeId);
            }, $classes);
            /** @var array<int, IghClasse> $classesEntities */
            $classesEntities = array_filter($classesEntities);

            /** @var array<int, ?string> $classesLibelles */
            $classesLibelles = array_map(function (IghClasse $classeId): ?string {
                return $classeId->getLibelle();
            }, $classesEntities);
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
            $classementsEntities = array_map(function (string $classementId) {
                return $this->classementRepository->find($classementId);
            }, $classements);
            /** @var array<int, Classement> $classementsEntities */
            $classementsEntities = array_filter($classementsEntities);

            /** @var array<int, ?string> $classementsLibelles */
            $classementsLibelles = array_map(function (Classement $classementId): ?string {
                return $classementId->getLibelle();
            }, $classementsEntities);
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
