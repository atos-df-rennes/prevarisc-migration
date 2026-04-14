<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\CommissionRepository;
use App\Repository\DossierTypeRepository;
use App\Repository\GroupementRepository;
use App\Service\Search as SearchService;
use App\ValueObject\SearchPagination;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur de recherche.
 * Port direct de SearchController (legacy).
 */
class SearchController extends AbstractController
{
    /** @var SearchService */
    private $searchService;

    /** @var GroupementRepository */
    private $groupementRepository;

    /** @var CommissionRepository */
    private $commissionRepository;

    /** @var DossierTypeRepository */
    private $dossierTypeRepository;

    /** @var Connection */
    private $connection;

    /** @var bool */
    private $deactivatePlatau;

    public function __construct(
        SearchService $searchService,
        GroupementRepository $groupementRepository,
        CommissionRepository $commissionRepository,
        DossierTypeRepository $dossierTypeRepository,
        Connection $connection,
        bool $deactivatePlatau
    ) {
        $this->searchService = $searchService;
        $this->groupementRepository = $groupementRepository;
        $this->commissionRepository = $commissionRepository;
        $this->dossierTypeRepository = $dossierTypeRepository;
        $this->connection = $connection;
        $this->deactivatePlatau = $deactivatePlatau;
    }

    /**
     * Recherche d'établissements.
     *
     * @Route("/rechercher/etablissement", name="search_etablissement", methods={"GET"})
     */
    public function etablissement(Request $request): Response
    {
        // Données de référence pour les filtres
        /** @var array<int, array<string, mixed>> $DB_genre */
        $DB_genre = $this->connection->executeQuery(
            'SELECT * FROM genre ORDER BY ID_GENRE ASC'
        )->fetchAllAssociative();

        /** @var array<int, array<string, mixed>> $DB_statut */
        $DB_statut = $this->connection->executeQuery(
            'SELECT * FROM statut ORDER BY LIBELLE_STATUT ASC'
        )->fetchAllAssociative();

        /** @var array<int, array<string, mixed>> $DB_avis */
        $DB_avis = $this->connection->executeQuery(
            'SELECT * FROM avis ORDER BY ID_AVIS ASC'
        )->fetchAllAssociative();

        /** @var array<int, array<string, mixed>> $DB_classe */
        $DB_classe = $this->connection->executeQuery(
            'SELECT * FROM classe ORDER BY LIBELLE_CLASSE ASC'
        )->fetchAllAssociative();

        /** @var array<int, array<string, mixed>> $DB_categorie */
        $DB_categorie = $this->connection->executeQuery(
            'SELECT * FROM categorie ORDER BY LIBELLE_CATEGORIE ASC'
        )->fetchAllAssociative();

        /** @var array<int, array<string, mixed>> $DB_typeactivite */
        $DB_typeactivite = $this->connection->executeQuery(
            'SELECT typeactivite.*, type.LIBELLE_TYPE FROM typeactivite
             LEFT JOIN type ON typeactivite.ID_TYPE = type.ID_TYPE
             ORDER BY type.LIBELLE_TYPE ASC, typeactivite.LIBELLE_ACTIVITE ASC'
        )->fetchAllAssociative();

        /** @var array<int, array<string, mixed>> $DB_famille */
        $DB_famille = $this->connection->executeQuery(
            'SELECT * FROM famille ORDER BY LIBELLE_FAMILLE ASC'
        )->fetchAllAssociative();

        /** @var array<int, array<string, mixed>> $DB_commission */
        $DB_commission = $this->connection->executeQuery(
            'SELECT * FROM commission ORDER BY ID_COMMISSION ASC'
        )->fetchAllAssociative();

        $DB_groupementterritorial = $this->groupementRepository->findTerritoriaux();
        $liste_prev = $this->searchService->listePrevActifs();

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
            try {
                $page = max(1, $request->query->getInt('page', 1));

                $label = $this->getStringQueryParam($request, 'label');
                $identifiant = null;
                if (null !== $label && '' !== $label && '#' === $label[0]) {
                    $subResult = substr($label, 1);
                    $identifiant = is_string($subResult) ? $subResult : '';
                    $label = null;
                }

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
                $groupements_territoriaux = $this->getArrayQueryParam($request, 'groupements_territoriaux');
                if (null === $groupements_territoriaux && null !== $user && $user->getGroupements()->count() > 0) {
                    $groupements_territoriaux = [];
                    foreach ($user->getGroupements() as $groupement) {
                        if (null !== $groupement->getId()) {
                            $groupements_territoriaux[] = (int) $groupement->getId();
                        }
                    }
                }

                $search = $this->searchService->etablissements(
                    $label,
                    $identifiant,
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
            'DB_avis' => $DB_avis,
            'DB_classe' => $DB_classe,
            'DB_categorie' => $DB_categorie,
            'DB_typeactivite' => $DB_typeactivite,
            'DB_famille' => $DB_famille,
            'DB_commission' => $DB_commission,
            'DB_groupementterritorial' => $DB_groupementterritorial,
            'liste_prev' => $liste_prev,
            'results' => $results,
            'user' => $user,
            'is_allowed_export_calc' => false,
        ]);
    }

    /**
     * Recherche de dossiers.
     *
     * @Route("/rechercher/dossier", name="search_dossier", methods={"GET"})
     */
    public function dossier(Request $request): Response
    {
        $DB_type = $this->dossierTypeRepository->findAll();
        $array_commissions = $this->commissionRepository->getCommissionsAndTypes();
        $DB_groupementterritorial = $this->groupementRepository->findTerritoriaux();
        $liste_prev = $this->searchService->listePrevActifs();

        /** @var array<int, array<string, mixed>> $array_communes */
        $array_communes = $this->connection->executeQuery(
            'SELECT * FROM adressecommune ORDER BY LIBELLE_COMMUNE ASC'
        )->fetchAllAssociative();

        /** @var array<int, array<string, mixed>> $array_voies */
        $array_voies = [];
        $communeParam = $this->getStringQueryParam($request, 'commune');
        if ($request->query->count() > 0 && null !== $communeParam) {
            $array_voies = $this->connection->executeQuery(
                'SELECT * FROM adresserue WHERE NUMINSEE_COMMUNE = ?',
                [$communeParam]
            )->fetchAllAssociative();
        }

        /** @var array<int, array<string, mixed>> $array_numeros */
        $array_numeros = [];
        $voieParam = $this->getStringQueryParam($request, 'voie');
        if ($request->query->count() > 0 && null !== $voieParam) {
            $array_numeros = $this->connection->executeQuery(
                'SELECT DISTINCT NUMERO_ADRESSE FROM etablissementadresse WHERE ID_RUE = ? ORDER BY NUMERO_ADRESSE',
                [(int) $voieParam]
            )->fetchAllAssociative();
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
            try {
                $page = max(1, $request->query->getInt('page', 1));

                $num_doc_urba = $this->getStringQueryParam($request, 'permis');

                $objetRaw = $this->getStringQueryParam($request, 'objet');
                $objet = (null !== $objetRaw && '' !== $objetRaw && '#' !== substr($objetRaw, 0, 1))
                    ? $objetRaw
                    : null;

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
                $groupements_territoriaux = $this->getArrayQueryParam($request, 'groupements_territoriaux');
                if (null === $groupements_territoriaux && null !== $user && $user->getGroupements()->count() > 0) {
                    $groupements_territoriaux = [];
                    foreach ($user->getGroupements() as $groupement) {
                        if (null !== $groupement->getId()) {
                            $groupements_territoriaux[] = (int) $groupement->getId();
                        }
                    }
                }

                $criteresRecherche['groupements_territoriaux'] = $groupements_territoriaux;

                $search = $this->searchService->dossiers(
                    $types,
                    $objet,
                    $num_doc_urba,
                    null,
                    null,
                    50,
                    $page,
                    $criteresRecherche
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
            'is_allowed_export_calc' => false,
        ]);
    }

    /**
     * AJAX : liste des établissements enfants d'un parent.
     *
     * @Route("/rechercher/display-ajax-search-etablissement", name="search_ajax_etablissement", methods={"POST"})
     */
    public function ajaxEtablissement(Request $request): Response
    {
        $parentIdRaw = $request->request->get('parent', 0);
        $parentId = is_numeric($parentIdRaw) ? (int) $parentIdRaw : 0;

        $search = $this->searchService->etablissements(
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
     * AJAX : liste des dossiers enfants d'un parent.
     *
     * @Route("/rechercher/display-ajax-search-dossier", name="search_ajax_dossier", methods={"POST"})
     */
    public function ajaxDossier(Request $request): Response
    {
        $parentIdRaw = $request->request->get('parent', 0);
        $parentId = is_numeric($parentIdRaw) ? (int) $parentIdRaw : 0;

        $search = $this->searchService->dossiers(null, null, null, $parentId, null, 100);

        /** @var array<int, array<string, mixed>> $data */
        $data = array_values($search['results']);

        $html = "<ul class='recherche_liste'>";
        foreach ($data as $item) {
            $html .= $this->renderView('search/results/_dossier.html.twig', ['item' => $item]);
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
