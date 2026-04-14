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
use Symfony\Component\HttpFoundation\StreamedResponse;
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
            // Export Calc
            if ($request->query->has('Exporter') && $this->isGranted('export_ets')) {
                try {
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
                    $groupements_territoriaux = $this->getArrayQueryParam($request, 'groupements_territoriaux');

                    $search = $this->searchService->extractionEtablissements(
                        $label, $identifiant, $genres, $categories, $classes, $familles, $types_activites,
                        $avis_favorable, $statuts, $local_sommeil, null, null, null,
                        $city, $street, $number, $commissions, $groupements_territoriaux, $preventionniste
                    );

                    $filename = 'Export_Etablissements_'.date('Y-m-d_H-i-s').'.ods';
                    $rows = $search['results'];

                    return new StreamedResponse(function () use ($rows): void {
                        $objPHPExcel = new \PHPExcel();
                        $objPHPExcel->setActiveSheetIndex(0);

                        $sheet = $objPHPExcel->getActiveSheet();
                        $sheet->setTitle('Liste des établissements');

                        $objPHPExcel->getDefaultStyle()->getFont()->setName('Arial')->setSize(10)->setBold(false);
                        $sheet->getDefaultRowDimension()->setRowHeight(-1);

                        $styleArray = [
                            'borders' => ['allborders' => ['style' => \PHPExcel_Style_Border::BORDER_THIN]],
                        ];
                        $sheet->getStyle('A1:X1')->applyFromArray($styleArray);
                        $sheet->getStyle('A1:X1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('A1:X1')->getFont()->setSize(11)->setBold(true);

                        $sheet->setCellValueByColumnAndRow(0, '1', 'Commune');
                        $sheet->setCellValueByColumnAndRow(1, '1', 'Catégorie');
                        $sheet->setCellValueByColumnAndRow(2, '1', 'Type');
                        $sheet->setCellValueByColumnAndRow(3, '1', 'Activité');
                        $sheet->setCellValueByColumnAndRow(4, '1', 'Commission compétente');
                        $sheet->setCellValueByColumnAndRow(5, '1', 'Code/identifiant établissement');
                        $sheet->setCellValueByColumnAndRow(6, '1', 'Libellé établissement');
                        $sheet->setCellValueByColumnAndRow(7, '1', 'Statut');
                        $sheet->setCellValueByColumnAndRow(8, '1', 'Avis');
                        $sheet->setCellValueByColumnAndRow(9, '1', 'Date du dernier avis');
                        $sheet->setCellValueByColumnAndRow(10, '1', 'Date du premier avis favorable');
                        $sheet->setCellValueByColumnAndRow(11, '1', 'Date du premier avis défavorable consécutif');
                        $sheet->setCellValueByColumnAndRow(12, '1', 'Effectif total');
                        $sheet->setCellValueByColumnAndRow(13, '1', 'Effectif public');
                        $sheet->setCellValueByColumnAndRow(14, '1', 'Effectif personnel');
                        $sheet->setCellValueByColumnAndRow(15, '1', 'Date de dernière visite');
                        $sheet->setCellValueByColumnAndRow(16, '1', 'Date de prochaine visite périodique');
                        $sheet->setCellValueByColumnAndRow(17, '1', 'Date de visite prévue');
                        $sheet->setCellValueByColumnAndRow(18, '1', 'Adresse');
                        $sheet->setCellValueByColumnAndRow(19, '1', 'Groupement territorial compétent');
                        $sheet->setCellValueByColumnAndRow(20, '1', 'Libellé du père/site');
                        $sheet->setCellValueByColumnAndRow(21, '1', 'Genre');
                        $sheet->setCellValueByColumnAndRow(22, '1', 'Préventionniste');
                        $sheet->setCellValueByColumnAndRow(23, '1', 'Présence de locaux à sommeil');

                        $ligne = 2;
                        foreach ($rows as $row) {
                            $sheet->setCellValueByColumnAndRow(0, (string) $ligne, $row['LIBELLE_COMMUNE'] ?? '');
                            $sheet->setCellValueByColumnAndRow(1, (string) $ligne, $row['LIBELLE_CATEGORIE'] ?? '');
                            $sheet->setCellValueByColumnAndRow(2, (string) $ligne, $row['LIBELLE_TYPE'] ?? '');
                            $sheet->setCellValueByColumnAndRow(3, (string) $ligne, $row['LIBELLE_ACTIVITE'] ?? '');
                            $sheet->setCellValueByColumnAndRow(4, (string) $ligne, $row['LIBELLE_COMMISSION'] ?? '');
                            $sheet->setCellValueByColumnAndRow(5, (string) $ligne, $row['NUMEROID_ETABLISSEMENT'] ?? '');
                            $sheet->setCellValueByColumnAndRow(6, (string) $ligne, $row['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
                            $sheet->setCellValueByColumnAndRow(7, (string) $ligne, $row['LIBELLE_STATUT'] ?? '');
                            $sheet->setCellValueByColumnAndRow(8, (string) $ligne, $row['LIBELLE_AVIS'] ?? '');

                            if ('' !== ($row['DATE_DERNIER_AVIS'] ?? '')) {
                                $dateDernierAvis = preg_split('/-|\//', (string) $row['DATE_DERNIER_AVIS']);
                                if (is_array($dateDernierAvis) && isset($dateDernierAvis[0], $dateDernierAvis[1], $dateDernierAvis[2])) {
                                    if (!is_numeric($dateDernierAvis[2])) {
                                        $dateDernierAvis[2] = substr($dateDernierAvis[2], 0, 2);
                                    }

                                    $datetimeDernierAvis = \PHPExcel_Shared_Date::FormattedPHPToExcel((int) $dateDernierAvis[0], (int) $dateDernierAvis[1], (int) $dateDernierAvis[2]);
                                    $sheet->setCellValueByColumnAndRow(9, (string) $ligne, $datetimeDernierAvis);
                                    $sheet->getStyleByColumnAndRow(9, $ligne)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
                                }
                            }

                            if ('' !== ($row['DATE_PREMIER_AVIS_FAVORABLE'] ?? '')) {
                                $datePremierAvisFav = preg_split('/-|\//', (string) $row['DATE_PREMIER_AVIS_FAVORABLE']);
                                if (is_array($datePremierAvisFav) && isset($datePremierAvisFav[0], $datePremierAvisFav[1], $datePremierAvisFav[2])) {
                                    if (!is_numeric($datePremierAvisFav[2])) {
                                        $datePremierAvisFav[2] = substr($datePremierAvisFav[2], 0, 2);
                                    }

                                    $dtPremierAvisFav = \PHPExcel_Shared_Date::FormattedPHPToExcel((int) $datePremierAvisFav[0], (int) $datePremierAvisFav[1], (int) $datePremierAvisFav[2]);
                                    $sheet->setCellValueByColumnAndRow(10, (string) $ligne, $dtPremierAvisFav);
                                    $sheet->getStyleByColumnAndRow(10, $ligne)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
                                }
                            }

                            if ('' !== ($row['DATE_PREMIER_AVIS_DEFAVORABLE_CONSECUTIF'] ?? '')) {
                                $datePremierAvisDefCons = preg_split('/-|\//', (string) $row['DATE_PREMIER_AVIS_DEFAVORABLE_CONSECUTIF']);
                                if (is_array($datePremierAvisDefCons) && isset($datePremierAvisDefCons[0], $datePremierAvisDefCons[1], $datePremierAvisDefCons[2])) {
                                    if (!is_numeric($datePremierAvisDefCons[2])) {
                                        $datePremierAvisDefCons[2] = substr($datePremierAvisDefCons[2], 0, 2);
                                    }

                                    $dtDefCons = \PHPExcel_Shared_Date::FormattedPHPToExcel((int) $datePremierAvisDefCons[0], (int) $datePremierAvisDefCons[1], (int) $datePremierAvisDefCons[2]);
                                    $sheet->setCellValueByColumnAndRow(11, (string) $ligne, $dtDefCons);
                                    $sheet->getStyleByColumnAndRow(11, $ligne)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
                                }
                            }

                            $sheet->setCellValueByColumnAndRow(12, (string) $ligne, (int) ($row['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? 0) + (int) ($row['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? 0));
                            $sheet->setCellValueByColumnAndRow(13, (string) $ligne, $row['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? '');
                            $sheet->setCellValueByColumnAndRow(14, (string) $ligne, $row['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? '');

                            if ('' !== ($row['DATE_DERNIERE_VISITE'] ?? '')) {
                                $dateDerniereVisite = preg_split('/-|\//', (string) $row['DATE_DERNIERE_VISITE']);
                                if (is_array($dateDerniereVisite) && isset($dateDerniereVisite[0], $dateDerniereVisite[1], $dateDerniereVisite[2])) {
                                    if (!is_numeric($dateDerniereVisite[2])) {
                                        $dateDerniereVisite[2] = substr($dateDerniereVisite[2], 0, 2);
                                    }

                                    $datetimeDerniereVisite = \PHPExcel_Shared_Date::FormattedPHPToExcel((int) $dateDerniereVisite[0], (int) $dateDerniereVisite[1], (int) $dateDerniereVisite[2]);
                                    $sheet->setCellValueByColumnAndRow(15, (string) $ligne, $datetimeDerniereVisite);
                                    $sheet->getStyleByColumnAndRow(15, $ligne)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);

                                    if (0 != $row['PERIODICITE_ETABLISSEMENTINFORMATIONS']) {
                                        $periodicite = (int) $row['PERIODICITE_ETABLISSEMENTINFORMATIONS'];
                                        $tsDateVisite = strtotime((string) ($row['DATE_DERNIERE_VISITE'] ?? ''));
                                        if (false !== $tsDateVisite) {
                                            $tsProchaine = strtotime('+'.$periodicite.' months', $tsDateVisite);
                                            if (false !== $tsProchaine) {
                                                $dateProchaineVisite = date('Y-m-j', $tsProchaine);
                                                $dateProchaineVisiteParts = preg_split('/-|\//', $dateProchaineVisite);
                                                if (is_array($dateProchaineVisiteParts) && count($dateProchaineVisiteParts) >= 3) {
                                                    $datetimeProchaineVisite = \PHPExcel_Shared_Date::FormattedPHPToExcel((int) $dateProchaineVisiteParts[0], (int) $dateProchaineVisiteParts[1], (int) $dateProchaineVisiteParts[2]);
                                                    $sheet->setCellValueByColumnAndRow(16, (string) $ligne, $datetimeProchaineVisite);
                                                    $sheet->getStyleByColumnAndRow(16, $ligne)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            if ('' !== ($row['DATE_VISITE_PREVUE'] ?? '')) {
                                $dateVisitePrevue = preg_split('/-|\//', (string) $row['DATE_VISITE_PREVUE']);
                                if (is_array($dateVisitePrevue) && isset($dateVisitePrevue[0], $dateVisitePrevue[1], $dateVisitePrevue[2])) {
                                    if (!is_numeric($dateVisitePrevue[2])) {
                                        $dateVisitePrevue[2] = substr($dateVisitePrevue[2], 0, 2);
                                    }

                                    $datetimeVisitePrevue = \PHPExcel_Shared_Date::FormattedPHPToExcel((int) $dateVisitePrevue[0], (int) $dateVisitePrevue[1], (int) $dateVisitePrevue[2]);
                                    $sheet->setCellValueByColumnAndRow(17, (string) $ligne, $datetimeVisitePrevue);
                                    $sheet->getStyleByColumnAndRow(17, $ligne)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
                                }
                            }

                            $sheet->setCellValueByColumnAndRow(18, (string) $ligne, ($row['NUMERO_ADRESSE'] ?? '').' '.($row['LIBELLE_RUE'] ?? '').' '.($row['COMPLEMENT_ADRESSE'] ?? '').' '.($row['CODEPOSTAL_COMMUNE'] ?? ''));
                            $sheet->setCellValueByColumnAndRow(19, (string) $ligne, $row['LIBELLE_GROUPEMENT'] ?? '');
                            $sheet->setCellValueByColumnAndRow(20, (string) $ligne, $row['LIBELLE_ETABLISSEMENT_PERE'] ?? '');
                            $sheet->setCellValueByColumnAndRow(21, (string) $ligne, $row['LIBELLE_GENRE'] ?? '');
                            $sheet->setCellValueByColumnAndRow(22, (string) $ligne, ($row['PRENOM_UTILISATEURINFORMATIONS'] ?? '').' '.($row['NOM_UTILISATEURINFORMATIONS'] ?? ''));
                            $sheet->setCellValueByColumnAndRow(23, (string) $ligne, ($row['LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS'] ?? false) ? 'oui' : 'non');

                            ++$ligne;
                        }

                        $writer = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
                        $writer->save('php://output');
                    }, Response::HTTP_OK, [
                        'Content-Type' => 'application/vnd.oasis.opendocument.spreadsheet',
                        'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'L\'export a rencontré un problème. Veuillez rééssayez. ('.$e->getMessage().')');
                }
            }

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
            // Export Calc
            if ($request->query->has('Exporter') && $this->isGranted('export_doss')) {
                try {
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
                    $criteresRecherche['provenance'] = $this->getStringQueryParam($request, 'provenance');

                    $groupements_territoriaux = $this->getArrayQueryParam($request, 'groupements_territoriaux');
                    $criteresRecherche['groupements_territoriaux'] = $groupements_territoriaux;
                    $criteresRecherche['label'] = $this->getStringQueryParam($request, 'label');
                    $criteresRecherche['identifiant'] = null;

                    $search = $this->searchService->extractionDossiers($types, $objet, $num_doc_urba, null, null, $criteresRecherche);

                    $filename = 'Export_Dossiers_'.date('Y-m-d_H-i-s').'.ods';
                    $rows = $search['results'];
                    $provenanceForExport = $criteresRecherche['provenance'];

                    return new StreamedResponse(function () use ($rows, $provenanceForExport): void {
                        $objPHPExcel = new \PHPExcel();
                        $objPHPExcel->setActiveSheetIndex(0);

                        $sheet = $objPHPExcel->getActiveSheet();
                        $sheet->setTitle('Liste des dossiers');

                        $objPHPExcel->getDefaultStyle()->getFont()->setName('Arial')->setSize(10)->setBold(false);
                        $sheet->getDefaultRowDimension()->setRowHeight(-1);

                        $styleArray = [
                            'borders' => ['allborders' => ['style' => \PHPExcel_Style_Border::BORDER_THIN]],
                        ];
                        $sheet->getStyle('A1:V1')->applyFromArray($styleArray);
                        $sheet->getStyle('A1:V1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('A1:V1')->getFont()->setSize(11)->setBold(true);

                        $sheet->setCellValueByColumnAndRow(0, '1', 'Groupement');
                        $sheet->setCellValueByColumnAndRow(1, '1', 'Commune');
                        $sheet->setCellValueByColumnAndRow(2, '1', 'Catégorie');
                        $sheet->setCellValueByColumnAndRow(3, '1', 'Type');
                        $sheet->setCellValueByColumnAndRow(4, '1', 'Activité');
                        $sheet->setCellValueByColumnAndRow(5, '1', 'Code/identifiant établissement');
                        $sheet->setCellValueByColumnAndRow(6, '1', 'Libellé établissement');
                        $sheet->setCellValueByColumnAndRow(7, '1', 'Statut');
                        $sheet->setCellValueByColumnAndRow(8, '1', 'Genre');
                        $sheet->setCellValueByColumnAndRow(9, '1', 'Type du dossier');
                        $sheet->setCellValueByColumnAndRow(10, '1', 'Nature du dossier');
                        $sheet->setCellValueByColumnAndRow(11, '1', 'Date de création du dossier');
                        $sheet->setCellValueByColumnAndRow(12, '1', 'Objet du dossier');
                        $sheet->setCellValueByColumnAndRow(13, '1', 'Numéro document urbanisme');
                        $sheet->setCellValueByColumnAndRow(14, '1', 'Date de visite');
                        $sheet->setCellValueByColumnAndRow(15, '1', 'Date de la commission en salle');
                        $sheet->setCellValueByColumnAndRow(16, '1', 'Commission du dossier');
                        $sheet->setCellValueByColumnAndRow(17, '1', 'Avis rapporteur');
                        $sheet->setCellValueByColumnAndRow(18, '1', 'Avis commission');
                        $sheet->setCellValueByColumnAndRow(19, '1', 'Préventionniste en charge du dossier');
                        $sheet->setCellValueByColumnAndRow(20, '1', 'Pièces jointes ?');
                        $sheet->setCellValueByColumnAndRow(21, '1', 'Identifiant PLATAU');

                        $ligne = 2;
                        foreach ($rows as $row) {
                            $sheet->setCellValueByColumnAndRow(0, (string) $ligne, $row['LIBELLE_GROUPEMENT'] ?? '');
                            $sheet->setCellValueByColumnAndRow(1, (string) $ligne, $row['LIBELLE_COMMUNE'] ?? '');
                            $sheet->setCellValueByColumnAndRow(2, (string) $ligne, $row['LIBELLE_CATEGORIE'] ?? '');
                            $sheet->setCellValueByColumnAndRow(3, (string) $ligne, $row['LIBELLE_TYPE_ETABLISSEMENT'] ?? '');
                            $sheet->setCellValueByColumnAndRow(4, (string) $ligne, $row['LIBELLE_ACTIVITE'] ?? '');
                            $sheet->setCellValueByColumnAndRow(5, (string) $ligne, $row['NUMEROID_ETABLISSEMENT'] ?? '');
                            $sheet->setCellValueByColumnAndRow(6, (string) $ligne, $row['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
                            $sheet->setCellValueByColumnAndRow(7, (string) $ligne, $row['LIBELLE_STATUT'] ?? '');
                            $sheet->setCellValueByColumnAndRow(8, (string) $ligne, $row['LIBELLE_GENRE'] ?? '');
                            $sheet->setCellValueByColumnAndRow(9, (string) $ligne, $row['LIBELLE_DOSSIERTYPE'] ?? '');
                            $sheet->setCellValueByColumnAndRow(10, (string) $ligne, $row['LIBELLE_DOSSIERNATURE'] ?? '');

                            if ('' !== ($row['DATEINSERT_DOSSIER'] ?? '')) {
                                $dateCreationDossier = preg_split('/-|\//', (string) $row['DATEINSERT_DOSSIER']);
                                if (is_array($dateCreationDossier) && isset($dateCreationDossier[0], $dateCreationDossier[1], $dateCreationDossier[2])) {
                                    if (!is_numeric($dateCreationDossier[2])) {
                                        $dateCreationDossier[2] = substr($dateCreationDossier[2], 0, 2);
                                    }

                                    $datetimeCreationDossier = \PHPExcel_Shared_Date::FormattedPHPToExcel((int) $dateCreationDossier[0], (int) $dateCreationDossier[1], (int) $dateCreationDossier[2]);
                                    $sheet->setCellValueByColumnAndRow(11, (string) $ligne, $datetimeCreationDossier);
                                    $sheet->getStyleByColumnAndRow(11, $ligne)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
                                }
                            }

                            $sheet->setCellValueByColumnAndRow(12, (string) $ligne, $row['OBJET_DOSSIER'] ?? '');
                            $sheet->setCellValueByColumnAndRow(13, (string) $ligne, $row['NUM_DOCURBA'] ?? '');

                            if ('' !== ($row['DATEVISITE_DOSSIER'] ?? '')) {
                                $dateVisiteDossier = preg_split('/-|\//', (string) $row['DATEVISITE_DOSSIER']);
                                if (is_array($dateVisiteDossier) && isset($dateVisiteDossier[0], $dateVisiteDossier[1], $dateVisiteDossier[2])) {
                                    if (!is_numeric($dateVisiteDossier[2])) {
                                        $dateVisiteDossier[2] = substr($dateVisiteDossier[2], 0, 2);
                                    }

                                    $datetimeVisiteDossier = \PHPExcel_Shared_Date::FormattedPHPToExcel((int) $dateVisiteDossier[0], (int) $dateVisiteDossier[1], (int) $dateVisiteDossier[2]);
                                    $sheet->setCellValueByColumnAndRow(14, (string) $ligne, $datetimeVisiteDossier);
                                    $sheet->getStyleByColumnAndRow(14, $ligne)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
                                }
                            }

                            if ('' !== ($row['DATECOMM_DOSSIER'] ?? '')) {
                                $dateCommissionDossier = preg_split('/-|\//', (string) $row['DATECOMM_DOSSIER']);
                                if (is_array($dateCommissionDossier) && isset($dateCommissionDossier[0], $dateCommissionDossier[1], $dateCommissionDossier[2])) {
                                    if (!is_numeric($dateCommissionDossier[2])) {
                                        $dateCommissionDossier[2] = substr($dateCommissionDossier[2], 0, 2);
                                    }

                                    $datetimeCommissionDossier = \PHPExcel_Shared_Date::FormattedPHPToExcel((int) $dateCommissionDossier[0], (int) $dateCommissionDossier[1], (int) $dateCommissionDossier[2]);
                                    $sheet->setCellValueByColumnAndRow(15, (string) $ligne, $datetimeCommissionDossier);
                                    $sheet->getStyleByColumnAndRow(15, $ligne)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
                                }
                            }

                            $sheet->setCellValueByColumnAndRow(16, (string) $ligne, $row['LIBELLE_COMMISSION'] ?? '');
                            $sheet->setCellValueByColumnAndRow(17, (string) $ligne, $row['LIBELLE_AVIS_RAPPORTEUR'] ?? '');
                            $sheet->setCellValueByColumnAndRow(18, (string) $ligne, $row['LIBELLE_AVIS_COMMISSION'] ?? '');
                            $sheet->setCellValueByColumnAndRow(19, (string) $ligne, ($row['PRENOM_UTILISATEURINFORMATIONS'] ?? '').' '.($row['NOM_UTILISATEURINFORMATIONS'] ?? ''));

                            $sheet->setCellValueByColumnAndRow(20, (string) $ligne, '' !== ($row['ID_PIECEJOINTE'] ?? '') ? 'Oui' : 'Non');

                            if ('1' === $provenanceForExport) {
                                $sheet->setCellValueByColumnAndRow(21, (string) $ligne, $row['ID_PLATAU'] ?? '');
                            }

                            ++$ligne;
                        }

                        $writer = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
                        $writer->save('php://output');
                    }, Response::HTTP_OK, [
                        'Content-Type' => 'application/vnd.oasis.opendocument.spreadsheet',
                        'Content-Disposition' => 'attachment; filename='.$filename,
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'L\'export a rencontré un problème. Veuillez rééssayez. ('.$e->getMessage().')');
                }
            }

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
