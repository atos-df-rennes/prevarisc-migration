<?php

namespace App\Controller;

use App\Dto\Repository\DossierSearchResultDto;
use App\Entity\Utilisateur;
use App\Repository\AdresseRueRepository;
use App\Repository\CommissionRepository;
use App\Repository\CommuneRepository;
use App\Repository\DossierTypeRepository;
use App\Repository\EtablissementActiviteRepository;
use App\Repository\EtablissementAdresseRepository;
use App\Repository\EtablissementCategorieRepository;
use App\Repository\EtablissementStatutRepository;
use App\Repository\FamilleRepository;
use App\Repository\GenreRepository;
use App\Repository\GroupementRepository;
use App\Repository\IghClasseRepository;
use App\Service\DossierSearchService;
use App\Service\EtablissementSearchService;
use App\Service\SearchExport;
use App\ValueObject\SearchPagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur de recherche.
 * Port direct de SearchController (legacy).
 */
class SearchController extends AbstractController
{
    /** @var EtablissementSearchService */
    private $etablissementSearchService;

    /** @var DossierSearchService */
    private $dossierSearchService;

    /** @var GroupementRepository */
    private $groupementRepository;

    /** @var CommissionRepository */
    private $commissionRepository;

    /** @var DossierTypeRepository */
    private $dossierTypeRepository;

    /** @var GenreRepository */
    private $genreRepository;

    /** @var EtablissementStatutRepository */
    private $etablissementStatutRepository;

    /** @var EtablissementCategorieRepository */
    private $etablissementCategorieRepository;

    /** @var IghClasseRepository */
    private $ighClasseRepository;

    /** @var EtablissementActiviteRepository */
    private $etablissementActiviteRepository;

    /** @var FamilleRepository */
    private $familleRepository;

    /** @var CommuneRepository */
    private $communeRepository;

    /** @var AdresseRueRepository */
    private $adresseRueRepository;

    /** @var EtablissementAdresseRepository */
    private $etablissementAdresseRepository;

    /** @var SearchExport */
    private $searchExport;

    /** @var bool */
    private $deactivatePlatau;

    public function __construct(
        EtablissementSearchService $etablissementSearchService,
        DossierSearchService $dossierSearchService,
        GroupementRepository $groupementRepository,
        CommissionRepository $commissionRepository,
        DossierTypeRepository $dossierTypeRepository,
        GenreRepository $genreRepository,
        EtablissementStatutRepository $etablissementStatutRepository,
        EtablissementCategorieRepository $etablissementCategorieRepository,
        IghClasseRepository $ighClasseRepository,
        EtablissementActiviteRepository $etablissementActiviteRepository,
        FamilleRepository $familleRepository,
        CommuneRepository $communeRepository,
        AdresseRueRepository $adresseRueRepository,
        EtablissementAdresseRepository $etablissementAdresseRepository,
        bool $deactivatePlatau,
        SearchExport $searchExport
    ) {
        $this->etablissementSearchService = $etablissementSearchService;
        $this->dossierSearchService = $dossierSearchService;
        $this->groupementRepository = $groupementRepository;
        $this->commissionRepository = $commissionRepository;
        $this->dossierTypeRepository = $dossierTypeRepository;
        $this->genreRepository = $genreRepository;
        $this->etablissementStatutRepository = $etablissementStatutRepository;
        $this->etablissementCategorieRepository = $etablissementCategorieRepository;
        $this->ighClasseRepository = $ighClasseRepository;
        $this->etablissementActiviteRepository = $etablissementActiviteRepository;
        $this->familleRepository = $familleRepository;
        $this->communeRepository = $communeRepository;
        $this->adresseRueRepository = $adresseRueRepository;
        $this->etablissementAdresseRepository = $etablissementAdresseRepository;
        $this->deactivatePlatau = $deactivatePlatau;
        $this->searchExport = $searchExport;
    }

    /**
     * Recherche d'établissements.
     *
     * @Route("/rechercher/etablissement", name="search_etablissement", methods={"GET"})
     */
    public function etablissement(Request $request): Response
    {
        $this->denyAccessUnlessGranted('search_ets');

        // Données de référence pour les filtres
        $DB_genre = $this->genreRepository->findAllForSearch();
        $DB_statut = $this->etablissementStatutRepository->findAllForSearch();
        $DB_classe = $this->ighClasseRepository->findAllForSearch();
        $DB_categorie = $this->etablissementCategorieRepository->findAllForSearch();
        $DB_typeactivite = $this->etablissementActiviteRepository->findAllWithTypeForSearch();
        $DB_famille = $this->familleRepository->findAllForSearch();
        $DB_commission = $this->commissionRepository->findAllForSearch();

        $DB_groupementterritorial = $this->groupementRepository->findTerritoriaux();
        $liste_prev = $this->dossierSearchService->listePrevActifs();

        // Pré-sélection groupements si premier affichage (pas de paramètre Rechercher)
        /** @var Utilisateur|null $user */
        $user = null;
        if (!$request->query->has('Rechercher')) {
            $currentUser = $this->getUser();
            if ($currentUser instanceof Utilisateur) {
                $user = $currentUser;
            }
        }

        $results = null;

        if ($request->query->count() > 0) {
            // Export Calc
            if ($request->query->has('Exporter') && $this->isGranted('export_ets')) {
                try {
                    $label = $this->getStringQueryParam($request, 'label');

                    $genres = $this->getArrayQueryParam($request, 'genres');
                    $categories = $this->getArrayQueryParam($request, 'categories');
                    $classes = $this->getArrayQueryParam($request, 'classes');
                    $familles = $this->getArrayQueryParam($request, 'familles');
                    $types_activites = $this->getArrayQueryParam($request, 'types_activites');

                    $avisRaw = $this->getArrayQueryParam($request, 'avis');
                    $avis_favorable = null;
                    if (null !== $avisRaw && 1 === count($avisRaw)) {
                        $firstAvis = reset($avisRaw);
                        $avis_favorable = 'true' === (string) $firstAvis;
                    }

                    $statuts = $this->getArrayQueryParam($request, 'statuts');

                    $presencesRaw = $this->getArrayQueryParam($request, 'presences_local_sommeil');
                    $local_sommeil = null;
                    if (null !== $presencesRaw && 1 === count($presencesRaw)) {
                        $firstPresence = reset($presencesRaw);
                        $local_sommeil = 'true' === (string) $firstPresence;
                    }

                    $city = $this->getStringQueryParam($request, 'city');
                    $street = $this->getStringQueryParam($request, 'street');
                    $number = $this->getStringQueryParam($request, 'number');
                    $commissions = $this->getArrayQueryParam($request, 'commissions');
                    $preventionnisteRaw = $this->getStringQueryParam($request, 'preventionniste');
                    $preventionniste = null !== $preventionnisteRaw ? (int) $preventionnisteRaw : null;
                    $groupements_territoriaux = $this->getArrayQueryParam($request, 'groupements_territoriaux');

                    $search = $this->etablissementSearchService->extractionEtablissements(
                        $label, null, $genres, $categories, $classes, $familles, $types_activites,
                        $avis_favorable, $statuts, $local_sommeil, null, null, null,
                        $city, $street, $number, $commissions, $groupements_territoriaux, $preventionniste
                    );

                    if ($search['search_metadata']['count'] > 10000) {
                        $this->addFlash('error', 'L\'export Calc est limité à 10 000 éléments. Votre sélection en contient '.$search['search_metadata']['count'].'.');

                        return $this->redirectToRoute('search_etablissement', $request->query->all());
                    }

                    $filename = 'Export_Etablissements_'.date('Y-m-d_H-i-s').'.ods';
                    $rows = $search['results'];

                    return new StreamedResponse(function () use ($rows): void {
                        $this->searchExport->exportEtablissements($rows);
                    }, Response::HTTP_OK, [
                        'Content-Type' => 'application/vnd.oasis.opendocument.spreadsheet',
                        'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename),
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'L\'export a rencontré un problème. Veuillez rééssayez. ('.$e->getMessage().')');
                }
            }

            try {
                $page = max(1, $request->query->getInt('page', 1));

                $label = $this->getStringQueryParam($request, 'label');

                $genres = $this->getArrayQueryParam($request, 'genres');
                $categories = $this->getArrayQueryParam($request, 'categories');
                $classes = $this->getArrayQueryParam($request, 'classes');
                $familles = $this->getArrayQueryParam($request, 'familles');
                $types_activites = $this->getArrayQueryParam($request, 'types_activites');

                $avisRaw = $this->getArrayQueryParam($request, 'avis');
                $avis_favorable = null;
                if (null !== $avisRaw && 1 === count($avisRaw)) {
                    $firstAvis = reset($avisRaw);
                    $avis_favorable = 'true' === (string) $firstAvis;
                }

                $statuts = $this->getArrayQueryParam($request, 'statuts');

                $presencesRaw = $this->getArrayQueryParam($request, 'presences_local_sommeil');
                $local_sommeil = null;
                if (null !== $presencesRaw && 1 === count($presencesRaw)) {
                    $firstPresence = reset($presencesRaw);
                    $local_sommeil = 'true' === (string) $firstPresence;
                }

                $city = $this->getStringQueryParam($request, 'city');
                $street = $this->getStringQueryParam($request, 'street');
                $number = $this->getStringQueryParam($request, 'number');

                $commissions = $this->getArrayQueryParam($request, 'commissions');
                $preventionnisteRaw = $this->getStringQueryParam($request, 'preventionniste');
                $preventionniste = null !== $preventionnisteRaw ? (int) $preventionnisteRaw : null;

                // Groupements : depuis GET params ou pré-sélection utilisateur
                // Seuls les groupements de type territorial (ID_GROUPEMENTTYPE = 5) sont valides
                // pour ce filtre. Pré-sélectionner un groupement non-territorial renverrait
                // systématiquement zéro résultat.
                $groupements_territoriaux = $this->getArrayQueryParam($request, 'groupements_territoriaux');
                if (null === $groupements_territoriaux && null !== $user && $user->getGroupements()->count() > 0) {
                    $groupements_territoriaux = [];
                    foreach ($user->getGroupements() as $groupement) {
                        if (null !== $groupement->getId()
                            && null !== $groupement->getType()
                            && 5 === (int) $groupement->getType()->getId()
                        ) {
                            $groupements_territoriaux[] = (int) $groupement->getId();
                        }
                    }

                    // Aucun groupement territorial : ne pas appliquer de filtre
                    if (empty($groupements_territoriaux)) {
                        $groupements_territoriaux = null;
                    }
                }

                $search = $this->etablissementSearchService->etablissements(
                    $label,
                    null,
                    $genres,
                    $categories,
                    $classes,
                    $familles,
                    $types_activites,
                    $avis_favorable,
                    $statuts,
                    $local_sommeil,
                    null,
                    null,
                    null,
                    $city,
                    $street,
                    $number,
                    $commissions,
                    $groupements_territoriaux,
                    $preventionniste,
                    50,
                    $page
                );

                /** @var array<int, array<string, mixed>> $itemsArray */
                $itemsArray = array_values($search['results']);
                $results = new SearchPagination(
                    $itemsArray,
                    $search['search_metadata']['count'],
                    $page,
                    50
                );
            } catch (\Exception $e) {
                $this->addFlash('error', 'La recherche n\'a pas été effectuée correctement. Veuillez réessayer. ('.$e->getMessage().')');
            }
        }

        return $this->render('search/etablissement.html.twig', [
            'DB_genre' => $DB_genre,
            'DB_statut' => $DB_statut,
            'DB_classe' => $DB_classe,
            'DB_categorie' => $DB_categorie,
            'DB_typeactivite' => $DB_typeactivite,
            'DB_famille' => $DB_famille,
            'DB_commission' => $DB_commission,
            'DB_groupementterritorial' => $DB_groupementterritorial,
            'liste_prev' => $liste_prev,
            'results' => $results,
            'user' => $user,
            'is_allowed_export_calc' => $this->isGranted('export_ets'),
        ]);
    }

    /**
     * Recherche de dossiers.
     *
     * @Route("/rechercher/dossier", name="search_dossier", methods={"GET"})
     */
    public function dossier(Request $request): Response
    {
        $this->denyAccessUnlessGranted('search_dossiers');

        $DB_type = $this->dossierTypeRepository->findAll();
        $array_commissions = $this->commissionRepository->getCommissionsAndTypes();
        $DB_groupementterritorial = $this->groupementRepository->findTerritoriaux();
        $liste_prev = $this->dossierSearchService->listePrevActifs();

        $array_communes = $this->communeRepository->findAllForSearch();

        $array_voies = [];
        $communeParam = $this->getStringQueryParam($request, 'commune');
        if ($request->query->count() > 0 && null !== $communeParam) {
            $array_voies = $this->adresseRueRepository->findByCommune($communeParam);
        }

        $array_numeros = [];
        $voieParam = $this->getStringQueryParam($request, 'voie');
        if ($request->query->count() > 0 && null !== $voieParam) {
            $array_numeros = $this->etablissementAdresseRepository->findDistinctNumerosByRue((int) $voieParam);
        }

        // Validation du format de date (port du $checkDateFormat du legacy)
        $checkDateFormat = static function (?string $date): bool {
            if (null === $date || '' === $date) {
                return false;
            }

            $dateArgs = explode('/', $date);
            if (3 !== count($dateArgs)) {
                return false;
            }

            return checkdate((int) $dateArgs[1], (int) $dateArgs[0], (int) $dateArgs[2]);
        };

        // Pré-sélection groupements
        /** @var Utilisateur|null $user */
        $user = null;
        if (!$request->query->has('Rechercher')) {
            $currentUser = $this->getUser();
            if ($currentUser instanceof Utilisateur) {
                $user = $currentUser;
            }
        }

        $results = null;

        if ($request->query->count() > 0) {
            // Export Calc
            if ($request->query->has('Exporter') && $this->isGranted('export_doss')) {
                try {
                    $num_doc_urba = $this->getStringQueryParam($request, 'permis');
                    $objet = $this->getStringQueryParam($request, 'objet');

                    $types = $this->getArrayQueryParam($request, 'types');

                    /** @var array<string, mixed> $criteresRecherche */
                    $criteresRecherche = [];
                    $criteresRecherche['commissions'] = $this->getArrayQueryParam($request, 'commissions');
                    $criteresRecherche['avisCommission'] = $this->getArrayQueryParam($request, 'avisCommission');
                    $criteresRecherche['avisRapporteur'] = $this->getArrayQueryParam($request, 'avisRapporteur');

                    $avisDiffereRaw = $this->getArrayQueryParam($request, 'avisDiffere');
                    if (null !== $avisDiffereRaw && 1 === count($avisDiffereRaw)) {
                        $firstAvisDiffere = reset($avisDiffereRaw);
                        $criteresRecherche['avisDiffere'] = 'true' === (string) $firstAvisDiffere;
                    } else {
                        $criteresRecherche['avisDiffere'] = null;
                    }

                    $criteresRecherche['commune'] = $this->getStringQueryParam($request, 'commune');
                    $criteresRecherche['voie'] = $this->getStringQueryParam($request, 'voie');
                    $criteresRecherche['numero'] = $this->getStringQueryParam($request, 'numero');
                    $criteresRecherche['courrier'] = $this->getStringQueryParam($request, 'courrier');
                    $criteresRecherche['preventionniste'] = $this->getStringQueryParam($request, 'preventionniste');

                    $dateCreationStart = $this->getStringQueryParam($request, 'date-creation-start');
                    $criteresRecherche['dateCreationStart'] = $checkDateFormat($dateCreationStart) ? $dateCreationStart : null;
                    $dateCreationEnd = $this->getStringQueryParam($request, 'date-creation-end');
                    $criteresRecherche['dateCreationEnd'] = $checkDateFormat($dateCreationEnd) ? $dateCreationEnd : null;
                    $dateReceptionStart = $this->getStringQueryParam($request, 'date-reception-start');
                    $criteresRecherche['dateReceptionStart'] = $checkDateFormat($dateReceptionStart) ? $dateReceptionStart : null;
                    $dateReceptionEnd = $this->getStringQueryParam($request, 'date-reception-end');
                    $criteresRecherche['dateReceptionEnd'] = $checkDateFormat($dateReceptionEnd) ? $dateReceptionEnd : null;
                    $dateReponseStart = $this->getStringQueryParam($request, 'date-reponse-start');
                    $criteresRecherche['dateReponseStart'] = $checkDateFormat($dateReponseStart) ? $dateReponseStart : null;
                    $dateReponseEnd = $this->getStringQueryParam($request, 'date-reponse-end');
                    $criteresRecherche['dateReponseEnd'] = $checkDateFormat($dateReponseEnd) ? $dateReponseEnd : null;
                    $criteresRecherche['provenance'] = $this->getStringQueryParam($request, 'provenance');

                    $groupements_territoriaux = $this->getArrayQueryParam($request, 'groupements_territoriaux');
                    $criteresRecherche['groupements_territoriaux'] = $groupements_territoriaux;
                    $criteresRecherche['label'] = $this->getStringQueryParam($request, 'label');
                    $criteresRecherche['identifiant'] = null;

                    $search = $this->dossierSearchService->extractionDossiers($types, $objet, $num_doc_urba, null, null, $criteresRecherche);

                    if ($search['search_metadata']['count'] > 10000) {
                        $this->addFlash('error', 'L\'export Calc est limité à 10 000 éléments. Votre sélection en contient '.$search['search_metadata']['count'].'.');

                        return $this->redirectToRoute('search_dossier', $request->query->all());
                    }

                    $filename = 'Export_Dossiers_'.date('Y-m-d_H-i-s').'.ods';
                    $rows = $search['results'];
                    /** @var string|null $provenanceForExport */
                    $provenanceForExport = $criteresRecherche['provenance'];

                    return new StreamedResponse(function () use ($rows, $provenanceForExport): void {
                        $this->searchExport->exportDossiers($rows, $provenanceForExport);
                    }, Response::HTTP_OK, [
                        'Content-Type' => 'application/vnd.oasis.opendocument.spreadsheet',
                        'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename),
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'L\'export a rencontré un problème. Veuillez rééssayez. ('.$e->getMessage().')');
                }
            }

            try {
                $page = max(1, $request->query->getInt('page', 1));
                $num_doc_urba = $this->getStringQueryParam($request, 'permis');
                $objet = $this->getStringQueryParam($request, 'objet');

                $types = $this->getArrayQueryParam($request, 'types');

                /** @var array<string, mixed> $criteresRecherche */
                $criteresRecherche = [];
                $criteresRecherche['commissions'] = $this->getArrayQueryParam($request, 'commissions');
                $criteresRecherche['avisCommission'] = $this->getArrayQueryParam($request, 'avisCommission');
                $criteresRecherche['avisRapporteur'] = $this->getArrayQueryParam($request, 'avisRapporteur');

                $avisDiffereRaw = $this->getArrayQueryParam($request, 'avisDiffere');
                if (null !== $avisDiffereRaw && 1 === count($avisDiffereRaw)) {
                    $firstAvisDiffere = reset($avisDiffereRaw);
                    $criteresRecherche['avisDiffere'] = 'true' === (string) $firstAvisDiffere;
                } else {
                    $criteresRecherche['avisDiffere'] = null;
                }

                $criteresRecherche['commune'] = $this->getStringQueryParam($request, 'commune');
                $criteresRecherche['voie'] = $this->getStringQueryParam($request, 'voie');
                $criteresRecherche['numero'] = $this->getStringQueryParam($request, 'numero');
                $criteresRecherche['courrier'] = $this->getStringQueryParam($request, 'courrier');
                $criteresRecherche['preventionniste'] = $this->getStringQueryParam($request, 'preventionniste');

                $dateCreationStart = $this->getStringQueryParam($request, 'date-creation-start');
                $criteresRecherche['dateCreationStart'] = $checkDateFormat($dateCreationStart) ? $dateCreationStart : null;

                $dateCreationEnd = $this->getStringQueryParam($request, 'date-creation-end');
                $criteresRecherche['dateCreationEnd'] = $checkDateFormat($dateCreationEnd) ? $dateCreationEnd : null;

                $dateReceptionStart = $this->getStringQueryParam($request, 'date-reception-start');
                $criteresRecherche['dateReceptionStart'] = $checkDateFormat($dateReceptionStart) ? $dateReceptionStart : null;

                $dateReceptionEnd = $this->getStringQueryParam($request, 'date-reception-end');
                $criteresRecherche['dateReceptionEnd'] = $checkDateFormat($dateReceptionEnd) ? $dateReceptionEnd : null;

                $dateReponseStart = $this->getStringQueryParam($request, 'date-reponse-start');
                $criteresRecherche['dateReponseStart'] = $checkDateFormat($dateReponseStart) ? $dateReponseStart : null;

                $dateReponseEnd = $this->getStringQueryParam($request, 'date-reponse-end');
                $criteresRecherche['dateReponseEnd'] = $checkDateFormat($dateReponseEnd) ? $dateReponseEnd : null;

                $provenanceRaw = $this->getStringQueryParam($request, 'provenance');
                $criteresRecherche['provenance'] = $provenanceRaw;

                // Groupements territoriaux
                // Seuls les groupements de type territorial (ID_GROUPEMENTTYPE = 5) sont valides
                // pour ce filtre. Pré-sélectionner un groupement non-territorial renverrait
                // systématiquement zéro résultat.
                $groupements_territoriaux = $this->getArrayQueryParam($request, 'groupements_territoriaux');
                if (null === $groupements_territoriaux && null !== $user && $user->getGroupements()->count() > 0) {
                    $groupements_territoriaux = [];
                    foreach ($user->getGroupements() as $groupement) {
                        if (null !== $groupement->getId()
                            && null !== $groupement->getType()
                            && 5 === (int) $groupement->getType()->getId()
                        ) {
                            $groupements_territoriaux[] = (int) $groupement->getId();
                        }
                    }

                    // Aucun groupement territorial : ne pas appliquer de filtre
                    if (empty($groupements_territoriaux)) {
                        $groupements_territoriaux = null;
                    }
                }

                $criteresRecherche['groupements_territoriaux'] = $groupements_territoriaux;

                $search = $this->dossierSearchService->dossiers(
                    $types,
                    $objet,
                    $num_doc_urba,
                    null,
                    null,
                    50,
                    $page,
                    $criteresRecherche
                );

                /** @var array<int, DossierSearchResultDto> $itemsArray */
                $itemsArray = array_values($search['results']);
                $results = new SearchPagination(
                    $itemsArray,
                    $search['search_metadata']['count'],
                    $page,
                    50
                );
            } catch (\Exception $e) {
                $this->addFlash('error', 'La recherche n\'a pas été effectuée correctement. Veuillez réessayer. ('.$e->getMessage().')');
            }
        }

        return $this->render('search/dossier.html.twig', [
            'DB_type' => $DB_type,
            'array_commissions' => $array_commissions,
            'array_communes' => $array_communes,
            'array_voies' => $array_voies,
            'array_numeros' => $array_numeros,
            'DB_groupementterritorial' => $DB_groupementterritorial,
            'liste_prev' => $liste_prev,
            'results' => $results,
            'user' => $user,
            'platau_active' => !$this->deactivatePlatau,
            'is_allowed_export_calc' => $this->isGranted('export_doss'),
        ]);
    }

    /**
     * AJAX : liste des établissements enfants d'un parent.
     *
     * @Route("/rechercher/display-ajax-search-etablissement", name="search_ajax_etablissement", methods={"POST"})
     */
    public function ajaxEtablissement(Request $request): Response
    {
        $this->denyAccessUnlessGranted('search_ets');
        $parentIdRaw = $request->request->get('parent', 0);
        $parentId = is_numeric($parentIdRaw) ? (int) $parentIdRaw : 0;

        $search = $this->etablissementSearchService->etablissements(
            null, null, null, null, null, null, null, null, null, null,
            null, null, $parentId, null, null, null, null, null, null,
            100, 1
        );

        /** @var array<int, array<string, mixed>> $data */
        $data = array_values($search['results']);

        $html = "<ul class='recherche_liste'>";
        foreach ($data as $item) {
            $html .= $this->renderView('search/results/_etablissement.html.twig', ['item' => $item]);
        }

        $html .= '</ul>';

        return new Response($html);
    }

    /**
     * Extrait un paramètre GET de type string non vide, retourne null sinon.
     */
    private function getStringQueryParam(Request $request, string $key): ?string
    {
        $value = $request->query->get($key);
        if (is_string($value) && '' !== $value) {
            return $value;
        }

        return null;
    }

    /**
     * Extrait un paramètre GET de type array non vide, retourne null sinon.
     *
     * @return array<int|string, int|string>|null
     */
    private function getArrayQueryParam(Request $request, string $key): ?array
    {
        $value = $request->query->get($key);
        if (!is_array($value) || [] === $value) {
            return null;
        }

        /** @var array<int|string, int|string> $result */
        $result = [];
        foreach ($value as $k => $v) {
            if (is_string($v) || is_int($v)) {
                $result[$k] = $v;
            }
        }

        return [] === $result ? null : $result;
    }
}
