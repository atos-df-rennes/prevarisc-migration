<?php

namespace App\Service;

use App\Entity\Commission;
use App\Repository\ClassementRepository;
use App\Repository\CommissionRepository;
use App\Repository\CoucheCartoRepository;
use App\Repository\EtablissementActiviteRepository;
use App\Repository\EtablissementCategorieRepository;
use App\Repository\EtablissementStatutRepository;
use App\Repository\EtablissementTypeRepository;
use App\Repository\FamilleRepository;
use App\Repository\GenreRepository;
use App\Repository\IghClasseRepository;
use App\Repository\TypePlanRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Charge les données de référence nécessaires au formulaire d'ajout/édition d'un établissement.
 *
 * Ce service centralise le chargement de données communes aux actions add() et edit()
 * du contrôleur, évitant la duplication de code.
 */
final class EtablissementFormDataLoader
{
    /** @var GenreRepository */
    private $genreRepository;

    /** @var EtablissementStatutRepository */
    private $statutRepository;

    /** @var EtablissementCategorieRepository */
    private $categorieRepository;

    /** @var EtablissementTypeRepository */
    private $typeRepository;

    /** @var EtablissementActiviteRepository */
    private $activiteRepository;

    /** @var CommissionRepository */
    private $commissionRepository;

    /** @var TypePlanRepository */
    private $typePlanRepository;

    /** @var FamilleRepository */
    private $familleRepository;

    /** @var IghClasseRepository */
    private $ighClasseRepository;

    /** @var ClassementRepository */
    private $classementRepository;

    /** @var CoucheCartoRepository */
    private $coucheCartoRepository;

    /** @var ParameterBagInterface */
    private $parameterBag;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    public function __construct(
        GenreRepository $genreRepository,
        EtablissementStatutRepository $statutRepository,
        EtablissementCategorieRepository $categorieRepository,
        EtablissementTypeRepository $typeRepository,
        EtablissementActiviteRepository $activiteRepository,
        CommissionRepository $commissionRepository,
        TypePlanRepository $typePlanRepository,
        FamilleRepository $familleRepository,
        IghClasseRepository $ighClasseRepository,
        ClassementRepository $classementRepository,
        CoucheCartoRepository $coucheCartoRepository,
        ParameterBagInterface $parameterBag,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->genreRepository = $genreRepository;
        $this->statutRepository = $statutRepository;
        $this->categorieRepository = $categorieRepository;
        $this->typeRepository = $typeRepository;
        $this->activiteRepository = $activiteRepository;
        $this->commissionRepository = $commissionRepository;
        $this->typePlanRepository = $typePlanRepository;
        $this->familleRepository = $familleRepository;
        $this->ighClasseRepository = $ighClasseRepository;
        $this->classementRepository = $classementRepository;
        $this->coucheCartoRepository = $coucheCartoRepository;
        $this->parameterBag = $parameterBag;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @fixme: utiliser un Form Type Symfony
     * Charge toutes les données de référence pour le formulaire établissement.
     *
     * @return array<string, mixed> Tableau associatif prêt pour le render() Twig
     */
    public function load(): array
    {
        $activites = $this->activiteRepository->findAllWithType();

        // JSON des activités au format legacy pour le JS du formulaire
        $activitesJson = [];
        foreach ($activites as $activite) {
            $activiteType = $activite->getType();
            $activitesJson[] = [
                'ID_TYPE' => $activiteType ? $activiteType->getId() : null,
                'ID_TYPEACTIVITE' => $activite->getId(),
                'LIBELLE_ACTIVITE' => $activite->getLibelle(),
            ];
        }

        // Commissions groupées par type pour les optgroups du select
        /** @var array<int, array{libelle: string, items: Commission[]}> $commissionsData */
        $commissionsData = [];
        foreach ($this->commissionRepository->findAllWithType() as $commission) {
            $type = $commission->getType();
            $typeId = $type ? (int) $type->getId() : 0;
            $typeLibelle = $type ? (string) $type->getLibelle() : '';
            if (!isset($commissionsData[$typeId])) {
                $commissionsData[$typeId] = ['libelle' => $typeLibelle, 'items' => []];
            }

            $commissionsData[$typeId]['items'][] = $commission;
        }

        return [
            'genres' => $this->genreRepository->findAll(),
            'statuts' => $this->statutRepository->findAll(),
            'categories' => $this->categorieRepository->findAll(),
            'types' => $this->typeRepository->findAll(),
            'activites' => $activites,
            'activites_json' => json_encode($activitesJson, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
            'commissions' => $commissionsData,
            'types_plans' => $this->typePlanRepository->findAll(),
            'familles' => $this->familleRepository->findAll(),
            'igh_classes' => $this->ighClasseRepository->findAll(),
            'classements' => $this->classementRepository->findAllOrderedByLibelle(),
            'couches_cartographiques' => $this->coucheCartoRepository->findAllOrdered(),
            'can_edit_statut' => $this->authorizationChecker->isGranted('edit_statut'),
            'key_ign' => $this->parameterBag->get('app.ign_key'),
            'geoconcept_url' => $this->parameterBag->get('app.geoconcept_url'),
            'default_lon' => $this->parameterBag->get('app.carto_default_lon'),
            'default_lat' => $this->parameterBag->get('app.carto_default_lat'),
        ];
    }
}
