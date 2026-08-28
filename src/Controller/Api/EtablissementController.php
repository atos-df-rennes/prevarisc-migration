<?php

namespace App\Controller\Api;

use App\Constant\GenreEtablissement;
use App\Entity\Etablissement;
use App\Entity\EtablissementAdresse;
use App\Entity\Utilisateur;
use App\Repository\EtablissementAdresseRepository;
use App\Repository\EtablissementRepository;
use App\Repository\PeriodiciteRepository;
use App\Security\Voter\Etablissement\EtablissementVoter;
use App\Service\EtablissementAncestryService;
use App\Service\EtablissementSearchService;
use App\Service\Search\EtablissementAutocompleteNormalizer;
use App\Service\Search\SearchQueryHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API pour les établissements (autocomplete + valeurs par défaut).
 */
class EtablissementController extends AbstractController
{
    /**
     * Nombre minimum de caractères pour déclencher une recherche (parité legacy minChars: 3).
     */
    private const int SEARCH_MIN_LENGTH = 3;

    /**
     * Nombre de résultats par défaut (parité legacy Api_Service_Search::etablissements($count = 10)).
     */
    private const int SEARCH_DEFAULT_LIMIT = 10;

    private const int SEARCH_MAX_LIMIT = 50;

    /**
     * Nombre maximum d'établissements liés retournés (parité legacy : getItems(0, 500)).
     */
    private const int LIES_MAX_LIMIT = 500;

    public function __construct(
        private readonly EtablissementRepository $etablissementRepository,
        private readonly EtablissementAdresseRepository $etablissementAdresseRepository,
        private readonly PeriodiciteRepository $periodiciteRepository,
        private readonly EntityManagerInterface $em,
        private readonly EtablissementSearchService $etablissementSearchService,
        private readonly EtablissementAutocompleteNormalizer $autocompleteNormalizer,
        private readonly SearchQueryHelper $searchQueryHelper,
        private readonly EtablissementAncestryService $etablissementAncestryService
    ) {
    }

    /**
     * Récupération d'une adresse d'établissement par son ID.
     * Port de Api_Service_Etablissement::getAdresse.
     */
    #[Route(path: '/api/2.0/etablissement/get_adresse', name: 'api_etablissement_get_adresse', methods: ['GET'])]
    public function getAdresse(Request $request): JsonResponse
    {
        /** @var string|null $id */
        $id = $request->query->get('id');

        if (null === $id || '' === $id) {
            return new JsonResponse(['response' => null]);
        }

        $conn = $this->em->getConnection();

        /** @var array<string, mixed>|false $row */
        $row = $conn->executeQuery(
            'SELECT ea.*, ac.LIBELLE_COMMUNE, ac.CODEPOSTAL_COMMUNE, ar.LIBELLE_RUE, art.LIBELLE_RUETYPE, art.ABREVIATION_RUETYPE'
            .' FROM etablissementadresse ea'
            .' LEFT JOIN adressecommune ac ON ea.NUMINSEE_COMMUNE = ac.NUMINSEE_COMMUNE'
            .' LEFT JOIN adresserue ar ON ea.ID_RUE = ar.ID_RUE AND ea.NUMINSEE_COMMUNE = ar.NUMINSEE_COMMUNE'
            .' LEFT JOIN adresseruetype art ON ar.ID_RUETYPE = art.ID_RUETYPE'
            .' WHERE ea.ID_ADRESSE = ?',
            [(int) $id]
        )->fetchAssociative();

        if (false === $row) {
            return new JsonResponse(['response' => null]);
        }

        return new JsonResponse(['response' => $row]);
    }

    /**
     * Aperçu d'un établissement pour la bulle popover (bindEtsPopup, hover sur un lien établissement).
     * Port allégé de Api_Service_Etablissement::get() / Service_Etablissement::get() : ne recalcule que
     * les champs effectivement consommés par public/js/main-bs3.js (contrairement au legacy qui calculait
     * aussi rubriques, plans, diaporama, historique de dossiers, données pratiques, dates de visite, etc.).
     */
    #[Route(path: '/api/2.0/etablissement/apercu', name: 'api_etablissement_apercu', methods: ['GET'], options: ['expose' => true])]
    public function apercu(Request $request): JsonResponse
    {
        $id = filter_var($request->query->get('id'), FILTER_VALIDATE_INT);

        if (false === $id) {
            return new JsonResponse(['response' => null]);
        }

        $etablissement = $this->etablissementRepository->find($id);

        if (null === $etablissement || null !== $etablissement->getDateSuppression()) {
            return new JsonResponse(['response' => null]);
        }

        $this->denyAccessUnlessGranted(EtablissementVoter::LECTURE, $etablissement);

        $now = new \DateTime();
        $informations = $etablissement->getInformationsAtDate($now);

        if (null === $informations) {
            return new JsonResponse(['response' => null]);
        }

        $genre = $informations->getGenre()?->getId();
        $parentDirect = $etablissement->getParent();

        // Chaîne des ancêtres, racine en premier (port du climb+reverse legacy).
        $parents = $this->etablissementAncestryService->getAncetres($etablissement);

        // Pour une cellule, la catégorie affichée est celle de son établissement père direct (parité legacy).
        $categorieLibelle = GenreEtablissement::CELLULE === $genre && null !== $parentDirect
            ? $parentDirect->getInformationsAtDate($now)?->getCategorie()?->getLibelle()
            : $informations->getCategorie()?->getLibelle();

        $avis = $etablissement->getDossierDonnantAvis()?->getAvisCommission()?->getId();

        return new JsonResponse(['response' => [
            'general' => [
                'ID_ETABLISSEMENT' => $etablissement->getId(),
                'NUMEROID_ETABLISSEMENT' => $etablissement->getNumero(),
            ],
            'informations' => [
                'LIBELLE_ETABLISSEMENTINFORMATIONS' => $informations->getLibelle(),
                'LIBELLE_TYPE_PRINCIPAL' => $informations->getType()?->getLibelle(),
                'ID_GENRE' => $genre,
                'LIBELLE_CATEGORIE' => $categorieLibelle,
            ],
            'parents' => array_map(
                fn (Etablissement $parent): array => [
                    'LIBELLE_ETABLISSEMENTINFORMATIONS' => $parent->getInformationsAtDate($now)?->getLibelle(),
                    'LIBELLE_CATEGORIE' => $parent->getInformationsAtDate($now)?->getCategorie()?->getLibelle(),
                ],
                $parents
            ),
            'adresses' => array_map(
                static fn (EtablissementAdresse $adresse): array => [
                    'NUMERO_ADRESSE' => $adresse->getNumero(),
                    'LIBELLE_RUE' => $adresse->getRue()?->getLibelle(),
                    'CODEPOSTAL_COMMUNE' => $adresse->getCommune()?->getCodePostal(),
                    'LIBELLE_COMMUNE' => $adresse->getCommune()?->getLibelle(),
                ],
                $this->getAdressesEtablissement($id)
            ),
            // Le détail des enfants n'est utile au front que pour les sites (bindEtsPopup) ;
            // on évite la requête pour tous les autres genres.
            'etablissement_lies' => GenreEtablissement::SITE === $genre
                ? $this->getLibellesCommunesDefautFilsActifs($id)
                : [],
            'presence_avis_differe' => $etablissement->hasAvisDiffere(),
            'avis' => $avis,
            'preventionnistes' => array_map(
                static fn (Utilisateur $preventionniste): array => [
                    'NOM_UTILISATEURINFORMATIONS' => $preventionniste->getInformations()?->getNom(),
                    'PRENOM_UTILISATEURINFORMATIONS' => $preventionniste->getInformations()?->getPrenom(),
                ],
                $informations->getPreventionnistes()->toArray()
            ),
        ]]);
    }

    /**
     * Résolution de l'adresse d'un établissement (délègue à EtablissementAdresseRepository::get(),
     * qui porte déjà la logique legacy site → enfant au plus grand effectif / cellule → parent direct).
     *
     * @return array<int, EtablissementAdresse>
     */
    private function getAdressesEtablissement(int $id): array
    {
        return array_values(array_filter(
            $this->etablissementAdresseRepository->get($id),
            static fn (mixed $adresse): bool => $adresse instanceof EtablissementAdresse
        ));
    }

    /**
     * Communes des adresses par défaut des fils actifs d'un site.
     * Port allégé de Model_DbTable_Search::setItem('etablissement'), restreint à la seule colonne
     * consommée par la bulle popover (LIBELLE_COMMUNE_ADRESSE_DEFAULT), via EtablissementSearchService
     * pour réutiliser exactement la même logique de résolution (fallback site/cellule) que la recherche.
     *
     * @return array<int, array{LIBELLE_COMMUNE_ADRESSE_DEFAULT: string|null}>
     */
    private function getLibellesCommunesDefautFilsActifs(int $idEtablissementPere): array
    {
        $search = $this->etablissementSearchService->etablissements(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $idEtablissementPere,
            null,
            null,
            null,
            null,
            null,
            null,
            self::LIES_MAX_LIMIT,
            1
        );

        return array_values(array_map(
            static function (array $row): array {
                $libelle = $row['LIBELLE_COMMUNE_ADRESSE_DEFAULT'] ?? null;

                return ['LIBELLE_COMMUNE_ADRESSE_DEFAULT' => is_scalar($libelle) ? (string) $libelle : null];
            },
            $search['results']
        ));
    }

    /**
     * Recherche d'établissements pour autocomplétion.
     *
     * Route API commune à tous les écrans (recherche navbar, liaisons dossier,
     * établissements père/enfants). Délègue à EtablissementSearchService, comme le
     * legacy Api_Service_Search::etablissements() déléguait à Service_Search::etablissements() :
     * seul le libellé est transmis, ce qui permet de bénéficier de la recherche par
     * identifiant (« #123 » ou « E12/34 ») en plus de la recherche par libellé. Le
     * découpage label/identifiant reproduit celui effectué par SearchController (page de
     * recherche), via SearchQueryHelper::splitLabelAndIdentifiant().
     */
    #[Route(path: '/api/etablissements/search', name: 'api_etablissements_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        // « q » est le paramètre utilisé par les autocomplétions, « label » est le nom legacy
        $rawQuery = $request->query->get('q', $request->query->get('label', ''));
        $query = is_scalar($rawQuery) ? trim((string) $rawQuery) : '';

        // Seuil legacy : l'autocomplétion ne se déclenche qu'à partir de 3 caractères
        if (mb_strlen($query) < self::SEARCH_MIN_LENGTH) {
            return new JsonResponse([]);
        }

        $limit = filter_var($request->query->get('limit', self::SEARCH_DEFAULT_LIMIT), FILTER_VALIDATE_INT);
        if (false === $limit) {
            $limit = self::SEARCH_DEFAULT_LIMIT;
        }

        $limit = max(1, min(self::SEARCH_MAX_LIMIT, $limit));

        $page = filter_var($request->query->get('page', 1), FILTER_VALIDATE_INT);
        if (false === $page) {
            $page = 1;
        }

        $page = max(1, $page);

        [$label, $identifiant] = $this->searchQueryHelper->splitLabelAndIdentifiant($query);

        $search = $this->etablissementSearchService->etablissements(
            $label,
            $identifiant,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $limit,
            $page
        );

        return new JsonResponse($this->autocompleteNormalizer->normalize($search['results']), Response::HTTP_OK);
    }

    /**
     * Retourne les valeurs conseillées (commission, périodicité, local à sommeil, préventionnistes)
     * pour un établissement selon son genre, catégorie, type et commune.
     *
     * Port de Api_Service_Etablissement::getDefaultValues() / Service_Etablissement::getDefaultValues().
     */
    #[Route(path: '/api/2.0/etablissement/defaults_values', name: 'api_etablissement_defaults_values', methods: ['GET', 'POST'])]
    public function defaultsValues(Request $request): JsonResponse
    {
        // Récupération des paramètres (GET ou POST)
        /** @var array<string, mixed> $params */
        $params = array_merge($request->query->all(), $request->request->all());

        $rawGenre = $params['genre'] ?? null;
        $genre = is_scalar($rawGenre) ? (int) $rawGenre : null;
        $rawNuminsee = $params['numinsee'] ?? '';
        $numinsee = is_scalar($rawNuminsee) && '' !== (string) $rawNuminsee ? (string) $rawNuminsee : null;
        $rawType = $params['type'] ?? '';
        $type = is_scalar($rawType) && '' !== (string) $rawType ? (int) $rawType : null;
        $rawCategorie = $params['categorie'] ?? '';
        $categorie = is_scalar($rawCategorie) && '' !== (string) $rawCategorie ? (int) $rawCategorie : null;
        $rawLocalSommeil = $params['local_sommeil'] ?? null;
        $localSommeil = null !== $rawLocalSommeil ? (bool) $rawLocalSommeil : null;
        $rawClasse = $params['classe'] ?? '';
        $classe = is_scalar($rawClasse) && '' !== (string) $rawClasse ? (int) $rawClasse : null;
        $rawIdPere = $params['id_etablissement_pere'] ?? '';
        $idEtablissementPere = is_scalar($rawIdPere) && '' !== (string) $rawIdPere
            ? (int) $rawIdPere
            : null;

        if (null === $genre) {
            return new JsonResponse(['error' => 'Le paramètre genre est obligatoire.'], Response::HTTP_BAD_REQUEST);
        }

        $results = $this->computeDefaultValues(
            $genre,
            $type,
            $categorie,
            $localSommeil,
            $classe,
            $idEtablissementPere,
            $numinsee
        );

        return new JsonResponse($results, Response::HTTP_OK);
    }

    /**
     * Calcule les valeurs conseillées selon le genre et les critères fournis.
     * Port de Service_Etablissement::getDefaultValues().
     *
     * @param int         $genre               Identifiant du genre d'établissement
     * @param int|null    $type                Identifiant du type ERP
     * @param int|null    $categorie           Identifiant de la catégorie ERP
     * @param bool|null   $localSommeil        Présence d'un local à sommeil
     * @param int|null    $classe              Identifiant de la classe IGH
     * @param int|null    $idEtablissementPere Identifiant de l'établissement père
     * @param string|null $numinsee            Code INSEE de la commune
     *
     * @return array<string, mixed>
     */
    private function computeDefaultValues(
        int $genre,
        ?int $type,
        ?int $categorie,
        ?bool $localSommeil,
        ?int $classe,
        ?int $idEtablissementPere,
        ?string $numinsee
    ): array {
        $results = [];

        switch ($genre) {
            // Site
            case GenreEtablissement::SITE:
                // Préventionnistes des groupements de communes
                if (null !== $numinsee) {
                    $results['preventionnistes'] = $this->getPrev($numinsee, null);
                }

                break;

                // ERP
            case GenreEtablissement::ERP:
                // Suggestion local à sommeil : false par défaut si non renseigné
                if (null === $localSommeil) {
                    $results['local_sommeil'] = false;
                }

                // Suggestion local à sommeil selon type (types configurés dans PREVARISC_LOCAL_SOMMEIL_TYPES)
                if (null !== $type) {
                    /** @var string $localSommeilTypesParam */
                    $localSommeilTypesParam = $this->getParameter('app.local_sommeil_types');
                    $localSommeilTypes = array_map(intval(...), explode(';', $localSommeilTypesParam));

                    if (in_array($type, $localSommeilTypes, true)) {
                        $results['local_sommeil'] = true;
                    }
                }

                // Périodicité selon catégorie/type/local à sommeil
                if (!in_array(null, [$categorie, $type, $localSommeil], true)) {
                    $periodicite = $this->periodiciteRepository->gn4(
                        $categorie,
                        (string) $type,
                        $localSommeil
                    );
                    if (null !== $periodicite) {
                        $results['periodicite'] = $periodicite->getPeriodicite();
                    }
                }

                // Commission via établissement père si disponible
                if (null !== $idEtablissementPere) {
                    $pere = $this->etablissementRepository->find($idEtablissementPere);
                    if (null !== $pere) {
                        $commPere = $pere->getLatestInformations()->getCommission();
                        if (null !== $commPere) {
                            $results['commission'] = [
                                'ID_COMMISSION' => $commPere->getId(),
                                'LIBELLE_COMMISSION' => $commPere->getLibelle(),
                            ];
                        }
                    }
                }

                // Commission via règles si pas encore trouvée
                if (!isset($results['commission'])
                    && null !== $numinsee
                    && null !== $categorie
                    && null !== $type
                    && null !== $localSommeil
                ) {
                    $commission = $this->getCommission($numinsee, $categorie, $type, $localSommeil ? 1 : 0);
                    if (null !== $commission) {
                        $results['commission'] = $commission;
                    }
                }

                // Préventionnistes du site ou des groupements de communes
                if (null !== $numinsee || null !== $idEtablissementPere) {
                    $results['preventionnistes'] = $this->getPrev($numinsee, $idEtablissementPere);
                }

                break;

                // Cellule
            case GenreEtablissement::CELLULE:
                // Préventionnistes de l'établissement parent
                if (null !== $idEtablissementPere) {
                    $results['preventionnistes'] = $this->getPrev(null, $idEtablissementPere);
                }

                break;

                // IGH
            case GenreEtablissement::IGH:
                // Périodicité selon classe
                if (null !== $classe) {
                    $periodicite = $this->periodiciteRepository->gn4(0, (string) $classe, false);
                    if (null !== $periodicite) {
                        $results['periodicite'] = $periodicite->getPeriodicite();
                    }
                }

                // Commission via établissement père si disponible
                if (null !== $idEtablissementPere) {
                    $pere = $this->etablissementRepository->find($idEtablissementPere);
                    if (null !== $pere) {
                        $commPere = $pere->getLatestInformations()->getCommission();
                        if (null !== $commPere) {
                            $results['commission'] = [
                                'ID_COMMISSION' => $commPere->getId(),
                                'LIBELLE_COMMISSION' => $commPere->getLibelle(),
                            ];
                        }
                    }
                }

                // Commission via règles si pas encore trouvée
                if (!isset($results['commission']) && null !== $numinsee && null !== $classe) {
                    $commission = $this->getCommissionIGH($numinsee, $classe, 0);
                    if (null !== $commission) {
                        $results['commission'] = $commission;
                    }
                }

                // Préventionnistes du site ou des groupements de communes
                if (null !== $numinsee || null !== $idEtablissementPere) {
                    $results['preventionnistes'] = $this->getPrev($numinsee, $idEtablissementPere);
                }

                break;

                // Autres genres (Habitation, BUP, Camping, Manifestation temporaire, IOP, Zone)
            default:
                // Préventionnistes du site ou des groupements de communes
                if (null !== $numinsee || null !== $idEtablissementPere) {
                    $results['preventionnistes'] = $this->getPrev($numinsee, $idEtablissementPere);
                }

                break;
        }

        return $results;
    }

    /**
     * Retourne les préventionnistes suggérés selon l'établissement père ou la commune.
     * Port de Model_DbTable_Preventionniste::getPrev().
     *
     * Priorité : préventionnistes du père si existants, sinon préventionnistes
     * des groupements couvrant la commune.
     *
     * @param string|null $commune Code INSEE de la commune
     * @param int|null    $idPere  Identifiant de l'établissement père
     *
     * @return array<int, array{uid: mixed, nom: mixed, prenom: mixed}>|null
     */
    private function getPrev(?string $commune, ?int $idPere): ?array
    {
        $conn = $this->em->getConnection();
        $arrayResult = [];

        // Préventionnistes de l'établissement père (priorité sur la commune)
        if (null !== $idPere) {
            /** @var array<int, array<string, mixed>> $rows */
            $rows = $conn->executeQuery(
                'SELECT u.ID_UTILISATEUR, ui.NOM_UTILISATEURINFORMATIONS, ui.PRENOM_UTILISATEURINFORMATIONS'
                .' FROM utilisateur u'
                .' JOIN etablissementinformationspreventionniste eip ON eip.ID_UTILISATEUR = u.ID_UTILISATEUR'
                .' JOIN etablissementinformations ei ON ei.ID_ETABLISSEMENTINFORMATIONS = eip.ID_ETABLISSEMENTINFORMATIONS'
                .' JOIN utilisateurinformations ui ON ui.ID_UTILISATEURINFORMATIONS = u.ID_UTILISATEURINFORMATIONS'
                .' WHERE ei.ID_ETABLISSEMENT = ?',
                [$idPere]
            )->fetchAllAssociative();

            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $arrayResult[] = [
                        'uid' => $row['ID_UTILISATEUR'],
                        'nom' => $row['NOM_UTILISATEURINFORMATIONS'],
                        'prenom' => $row['PRENOM_UTILISATEURINFORMATIONS'],
                    ];
                }

                return $arrayResult;
            }
        }

        // Préventionnistes des groupements couvrant la commune
        if (null !== $commune && '' !== $commune) {
            /** @var array<int, array<string, mixed>> $rows */
            $rows = $conn->executeQuery(
                'SELECT DISTINCT u.ID_UTILISATEUR, ui.NOM_UTILISATEURINFORMATIONS, ui.PRENOM_UTILISATEURINFORMATIONS'
                .' FROM utilisateur u'
                .' JOIN groupementpreventionniste gp ON gp.ID_UTILISATEUR = u.ID_UTILISATEUR'
                .' JOIN groupementcommune gc ON gc.ID_GROUPEMENT = gp.ID_GROUPEMENT'
                .' JOIN utilisateurinformations ui ON ui.ID_UTILISATEURINFORMATIONS = u.ID_UTILISATEURINFORMATIONS'
                .' WHERE gc.NUMINSEE_COMMUNE = ?',
                [$commune]
            )->fetchAllAssociative();

            foreach ($rows as $row) {
                $arrayResult[] = [
                    'uid' => $row['ID_UTILISATEUR'],
                    'nom' => $row['NOM_UTILISATEURINFORMATIONS'],
                    'prenom' => $row['PRENOM_UTILISATEURINFORMATIONS'],
                ];
            }

            return $arrayResult;
        }

        return null;
    }

    /**
     * Retourne la commission compétente pour un ERP selon commune, catégorie, type et local à sommeil.
     * Port de Model_DbTable_Commission::getCommission().
     *
     * Parcourt les types de commission par ordre décroissant de priorité.
     * Vérifie d'abord la commune directe, puis les groupements de communes.
     *
     * @param string $numinsee     Code INSEE de la commune
     * @param int    $categorie    Identifiant de la catégorie ERP
     * @param int    $type         Identifiant du type ERP
     * @param int    $localSommeil 1 si local à sommeil, 0 sinon
     *
     * @return array{ID_COMMISSION: int, LIBELLE_COMMISSION: string}|null
     */
    private function getCommission(string $numinsee, int $categorie, int $type, int $localSommeil): ?array
    {
        $conn = $this->em->getConnection();

        /** @var list<array{ID_COMMISSIONTYPE: string}> $types */
        $types = $conn->executeQuery(
            'SELECT ID_COMMISSIONTYPE FROM commissiontype ORDER BY ID_COMMISSIONTYPE DESC'
        )->fetchAllAssociative();

        foreach ($types as $typeRow) {
            $commissionTypeId = (int) $typeRow['ID_COMMISSIONTYPE'];

            /** @var list<array{ID_GROUPEMENT: string|null, NUMINSEE_COMMUNE: string|null, ID_COMMISSION: string, LIBELLE_COMMISSION: string}> $results */
            $results = $conn->executeQuery(
                'SELECT cr.ID_GROUPEMENT, cr.NUMINSEE_COMMUNE, cr.ID_COMMISSION, c.LIBELLE_COMMISSION'
                .' FROM commissionregle cr'
                .' JOIN commission c ON c.ID_COMMISSION = cr.ID_COMMISSION'
                .' JOIN commissionregletype crt ON cr.ID_REGLE = crt.ID_REGLE'
                .' JOIN commissionreglecategorie crc ON cr.ID_REGLE = crc.ID_REGLE'
                .' JOIN commissionreglelocalsommeil crls ON cr.ID_REGLE = crls.ID_REGLE'
                .' WHERE crc.ID_CATEGORIE = ?'
                .'   AND crt.ID_TYPE = ?'
                .'   AND crls.LOCALSOMMEIL = ?'
                .'   AND c.ID_COMMISSIONTYPE = ?',
                [$categorie, $type, $localSommeil, $commissionTypeId]
            )->fetchAllAssociative();

            foreach ($results as $result) {
                $numinseeCommune = $result['NUMINSEE_COMMUNE'] ?? '';
                $idGroupement = $result['ID_GROUPEMENT'];

                if ('' !== $numinseeCommune) {
                    if ($numinseeCommune === $numinsee) {
                        return [
                            'ID_COMMISSION' => (int) $result['ID_COMMISSION'],
                            'LIBELLE_COMMISSION' => $result['LIBELLE_COMMISSION'],
                        ];
                    }
                } elseif (null !== $idGroupement) {
                    /** @var string|false $countResult */
                    $countResult = $conn->executeQuery(
                        'SELECT COUNT(*) FROM groupementcommune WHERE ID_GROUPEMENT = ? AND NUMINSEE_COMMUNE = ?',
                        [$idGroupement, $numinsee]
                    )->fetchOne();
                    $count = (int) $countResult;

                    if ($count > 0) {
                        return [
                            'ID_COMMISSION' => (int) $result['ID_COMMISSION'],
                            'LIBELLE_COMMISSION' => $result['LIBELLE_COMMISSION'],
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Retourne la commission compétente pour un IGH selon commune, classe et local à sommeil.
     * Port de Model_DbTable_Commission::getCommissionIGH().
     *
     * Parcourt les types de commission (hors type 5) par ordre croissant.
     * Vérifie d'abord la commune directe, puis les groupements de communes.
     *
     * @param string $numinsee     Code INSEE de la commune
     * @param int    $classe       Identifiant de la classe IGH
     * @param int    $localSommeil 1 si local à sommeil, 0 sinon
     *
     * @return array{ID_COMMISSION: int, LIBELLE_COMMISSION: string}|null
     */
    private function getCommissionIGH(string $numinsee, int $classe, int $localSommeil): ?array
    {
        $conn = $this->em->getConnection();

        /** @var list<array{ID_COMMISSIONTYPE: string}> $types */
        $types = $conn->executeQuery(
            'SELECT ID_COMMISSIONTYPE FROM commissiontype WHERE ID_COMMISSIONTYPE != 5 ORDER BY ID_COMMISSIONTYPE ASC'
        )->fetchAllAssociative();

        foreach ($types as $typeRow) {
            $commissionTypeId = (int) $typeRow['ID_COMMISSIONTYPE'];

            /** @var list<array{ID_GROUPEMENT: string|null, NUMINSEE_COMMUNE: string|null, ID_COMMISSION: string, LIBELLE_COMMISSION: string}> $results */
            $results = $conn->executeQuery(
                'SELECT cr.ID_GROUPEMENT, cr.NUMINSEE_COMMUNE, cr.ID_COMMISSION, c.LIBELLE_COMMISSION'
                .' FROM commissionregle cr'
                .' JOIN commission c ON c.ID_COMMISSION = cr.ID_COMMISSION'
                .' JOIN commissionregleclasse crcl ON cr.ID_REGLE = crcl.ID_REGLE'
                .' JOIN commissionreglelocalsommeil crls ON cr.ID_REGLE = crls.ID_REGLE'
                .' WHERE crcl.ID_CLASSE = ?'
                .'   AND crls.LOCALSOMMEIL = ?'
                .'   AND c.ID_COMMISSIONTYPE = ?',
                [$classe, $localSommeil, $commissionTypeId]
            )->fetchAllAssociative();

            foreach ($results as $result) {
                $numinseeCommune = $result['NUMINSEE_COMMUNE'] ?? '';
                $idGroupement = $result['ID_GROUPEMENT'];

                if ('' !== $numinseeCommune) {
                    if ($numinseeCommune === $numinsee) {
                        return [
                            'ID_COMMISSION' => (int) $result['ID_COMMISSION'],
                            'LIBELLE_COMMISSION' => $result['LIBELLE_COMMISSION'],
                        ];
                    }
                } elseif (null !== $idGroupement) {
                    /** @var string|false $countResult */
                    $countResult = $conn->executeQuery(
                        'SELECT COUNT(*) FROM groupementcommune WHERE ID_GROUPEMENT = ? AND NUMINSEE_COMMUNE = ?',
                        [$idGroupement, $numinsee]
                    )->fetchOne();
                    $count = (int) $countResult;

                    if ($count > 0) {
                        return [
                            'ID_COMMISSION' => (int) $result['ID_COMMISSION'],
                            'LIBELLE_COMMISSION' => $result['LIBELLE_COMMISSION'],
                        ];
                    }
                }
            }
        }

        return null;
    }
}
