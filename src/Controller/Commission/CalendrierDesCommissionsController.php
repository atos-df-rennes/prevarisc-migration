<?php

namespace App\Controller\Commission;

use App\Dto\Commission\CalendarEventDto;
use App\Dto\Commission\CommissionDatesDto;
use App\Dto\Commission\DateCommissionItemDto;
use App\Dto\Commission\DateCommissionLieeDto;
use App\Entity\Commission;
use App\Entity\DateCommission;
use App\Entity\PieceJointe;
use App\Entity\Utilisateur;
use App\Form\CalendrierDesCommissions\CommissionDatesType;
use App\Repository\CommissionRepository;
use App\Repository\CommissionTypeEvenementRepository;
use App\Repository\DateCommissionRepository;
use App\Repository\DossierAffectationRepository;
use App\Repository\PieceJointeRepository;
use App\Service\Commission\CalendarService;
use App\Service\Commission\CommissionDatesTransformer;
use App\Service\DataStore\DataStore;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// Routes du calendrier des commissions migrées vers Symfony.
// URL principale : /commissions/calendrier (≠ legacy /calendrier-des-commissions).
// Les routes AJAX partagent le même préfixe pour ne pas interférer avec le Zend legacy.
class CalendrierDesCommissionsController extends AbstractController
{
    /**
     * @var CalendarService
     */
    private $calendarService;

    /**
     * @var DateCommissionRepository
     */
    private $dateCommissionRepository;

    /**
     * @var CommissionDatesTransformer
     */
    private $commissionDatesTransformer;

    /**
     * @var CommissionTypeEvenementRepository
     */
    private $commissionTypeEvenementRepository;

    /**
     * @var CommissionRepository
     */
    private $commissionRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DossierAffectationRepository
     */
    private $dossierAffectationRepository;

    /**
     * @var DataStore
     */
    private $dataStore;

    /**
     * @var PieceJointeRepository
     */
    private $pieceJointeRepository;

    public function __construct(
        CalendarService $calendarService,
        DateCommissionRepository $dateCommissionRepository,
        CommissionDatesTransformer $commissionDatesTransformer,
        CommissionTypeEvenementRepository $commissionTypeEvenementRepository,
        CommissionRepository $commissionRepository,
        EntityManagerInterface $entityManager,
        DataStore $dataStore,
        DossierAffectationRepository $dossierAffectationRepository,
        PieceJointeRepository $pieceJointeRepository
    ) {
        $this->calendarService = $calendarService;
        $this->dateCommissionRepository = $dateCommissionRepository;
        $this->commissionDatesTransformer = $commissionDatesTransformer;
        $this->commissionTypeEvenementRepository = $commissionTypeEvenementRepository;
        $this->commissionRepository = $commissionRepository;
        $this->entityManager = $entityManager;
        $this->dataStore = $dataStore;
        $this->dossierAffectationRepository = $dossierAffectationRepository;
        $this->pieceJointeRepository = $pieceJointeRepository;
    }

    /**
     * Page principale du calendrier des commissions.
     *
     * @Route("/commissions/calendrier", name="calendrier_des_commissions_index", methods={"GET"}, options={"expose"=true})
     */
    public function index(Request $request): Response
    {
        /** @var string|null $rawIdComm */
        $rawIdComm = $request->query->get('idComm');
        $idComm = null !== $rawIdComm && '' !== $rawIdComm ? (int) $rawIdComm : null;

        $arrayCommissions = $this->commissionRepository->getCommissionsAndTypes();

        $isAdmin = $this->isGranted('gestion_commissions');
        $isViewAll = $this->isGranted('calendar_view_all');

        $user = $this->getUser();
        $userCommissionIds = [];
        if ($user instanceof Utilisateur) {
            $userCommissionIds = array_map(
                function (Commission $c): int {
                    return (int) $c->getId();
                },
                $user->getCommissions()->toArray()
            );
        }

        $urlWebcal = '';
        $securityKey = getenv('PREVARISC_SECURITY_KEY');
        if (false !== $securityKey && '' !== $securityKey) {
            $userId = ($user instanceof Utilisateur) ? (int) $user->getId() : 0;
            $baseScheme = $request->isSecure() ? 'webcals' : 'webcal';
            $host = $request->getHttpHost();
            $url = sprintf('/api/1.0/calendar?userid=%s&key=%s', $userId, $securityKey);
            if (null !== $idComm) {
                $url .= sprintf('&commission=%s', $idComm);
            }

            $urlWebcal = $baseScheme.'://'.$host.$url;
        }

        return $this->render('calendrier_des_commissions/index.html.twig', [
            'idComm' => $idComm,
            'array_commissions' => $arrayCommissions,
            'url_webcal' => $urlWebcal,
            'is_admin' => $isAdmin,
            'is_view_all' => $isViewAll,
            'user_commission_ids' => $userCommissionIds,
        ]);
    }

    /**
     * Autocomplete de recherche de commission.
     *
     * @Route("/commissions/calendrier/commissionselection", name="calendrier_des_commissions_commissionselection", methods={"GET"}, options={"expose"=true})
     */
    public function commissionSelection(Request $request): JsonResponse
    {
        /** @var string $q */
        $q = $request->query->get('q', '');

        if ('' === $q) {
            return $this->json([]);
        }

        $conn = $this->entityManager->getConnection();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative(
            'SELECT ID_COMMISSION, LIBELLE_COMMISSION FROM commission WHERE LIBELLE_COMMISSION LIKE :q',
            ['q' => '%'.$q.'%']
        );

        $items = array_map(function (array $row): array {
            return [
                'id' => $row['ID_COMMISSION'],
                'infos' => $row['LIBELLE_COMMISSION'],
            ];
        }, $rows);

        return $this->json($items);
    }

    /**
     * Dialogue de gestion des dates de commission (mega-switch).
     *
     * @Route("/commissions/calendrier/dialogcomm", name="calendrier_des_commissions_dialog_edit", methods={"POST"}, options={"expose"=true})
     */
    public function dialogComm(Request $request): Response
    {
        /** @var string $do */
        $do = $request->request->get('do', '');
        /** @var string $rawIdDateComm */
        $rawIdDateComm = $request->request->get('idDateComm', '0');
        $idDateComm = (int) $rawIdDateComm;

        $destructiveDos = ['valid_libelleCom', 'valid_typeCom', 'valid_dateCom', 'supp_dateCom', 'addDateS', 'makeDefaut'];
        if (in_array($do, $destructiveDos, true)) {
            /** @var string $csrfToken */
            $csrfToken = $request->request->get('_csrf_dialog', '');
            if (!$this->isCsrfTokenValid('calendrier_commissions_dialog', $csrfToken)) {
                return new Response('', Response::HTTP_FORBIDDEN);
            }
        }

        if ('edit' === $do) {
            $dateCommission = $this->dateCommissionRepository->find($idDateComm);
            if (null === $dateCommission) {
                throw $this->createNotFoundException(sprintf('Date de commission #%d introuvable', $idDateComm));
            }

            if (null === $dateCommission->getDateCommissionLiees()) {
                $idMaster = $dateCommission->getId();
            } else {
                $idMaster = $dateCommission->getDateCommissionLiees();
            }

            $listeDates = $this->dateCommissionRepository->getCommissionsQtypListing((int) $idMaster);

            return $this->render('calendrier_des_commissions/dialog/_edit.html.twig', [
                'dateCommission' => $dateCommission,
                'idMaster' => $idMaster,
                'listeDates' => $listeDates,
                'idComm' => $request->request->get('idComm'),
            ]);
        }

        if ('libelleCom' === $do) {
            $dateCommission = $this->dateCommissionRepository->find($idDateComm);
            if (null === $dateCommission) {
                throw $this->createNotFoundException(sprintf('Date de commission #%d introuvable', $idDateComm));
            }

            return $this->render('calendrier_des_commissions/dialog/_libelle_edit.html.twig', [
                'libelleDateComm' => $dateCommission->getLibelle(),
            ]);
        }

        if ('annule_libelleCom' === $do) {
            $dateCommission = $this->dateCommissionRepository->find($idDateComm);
            if (null === $dateCommission) {
                throw $this->createNotFoundException(sprintf('Date de commission #%d introuvable', $idDateComm));
            }

            return $this->render('calendrier_des_commissions/dialog/_libelle_display.html.twig', [
                'libelleDateComm' => $dateCommission->getLibelle(),
            ]);
        }

        if ('valid_libelleCom' === $do) {
            /** @var string $data */
            $data = $request->request->get('data', '');
            $dateCommission = $this->dateCommissionRepository->find($idDateComm);
            if (null === $dateCommission) {
                throw $this->createNotFoundException(sprintf('Date de commission #%d introuvable', $idDateComm));
            }

            if (null === $dateCommission->getDateCommissionLiees()) {
                $this->calendarService->updateLibelle($idDateComm, $data);
            } else {
                $this->calendarService->updateLibelle((int) $dateCommission->getDateCommissionLiees(), $data);
            }

            return $this->render('calendrier_des_commissions/dialog/_libelle_display.html.twig', [
                'libelleDateComm' => $data,
            ]);
        }

        if ('typeCom' === $do) {
            $listeCommType = $this->commissionTypeEvenementRepository->findAll();
            $dateCommission = $this->dateCommissionRepository->find($idDateComm);
            if (null === $dateCommission) {
                throw $this->createNotFoundException(sprintf('Date de commission #%d introuvable', $idDateComm));
            }

            $typeEvenement = $dateCommission->getTypeEvenement();

            return $this->render('calendrier_des_commissions/dialog/_type_edit.html.twig', [
                'listeCommType' => $listeCommType,
                'typeComSelect' => null !== $typeEvenement ? $typeEvenement->getId() : null,
            ]);
        }

        if ('valid_typeCom' === $do) {
            /** @var string $rawTypeSelect */
            $rawTypeSelect = $request->request->get('typeSelect', '0');
            $idTypeSelect = (int) $rawTypeSelect;
            $dateCommission = $this->dateCommissionRepository->find($idDateComm);
            if (null === $dateCommission) {
                throw $this->createNotFoundException(sprintf('Date de commission #%d introuvable', $idDateComm));
            }

            $idUtile = null !== $dateCommission->getDateCommissionLiees()
                ? (int) $dateCommission->getDateCommissionLiees()
                : $dateCommission->getId();

            $this->calendarService->updateType((int) $idUtile, $idTypeSelect);

            $infoType = $this->commissionTypeEvenementRepository->find($idTypeSelect);

            return $this->render('calendrier_des_commissions/dialog/_type_display.html.twig', [
                'libelleType' => null !== $infoType ? $infoType->getLibelle() : '',
            ]);
        }

        if ('annule_typeCom' === $do) {
            $dateCommission = $this->dateCommissionRepository->find($idDateComm);
            if (null === $dateCommission) {
                throw $this->createNotFoundException(sprintf('Date de commission #%d introuvable', $idDateComm));
            }

            $infoType = $dateCommission->getTypeEvenement();

            return $this->render('calendrier_des_commissions/dialog/_type_display.html.twig', [
                'libelleType' => null !== $infoType ? $infoType->getLibelle() : '',
            ]);
        }

        if ('dateComm' === $do) {
            $dateCommission = $this->dateCommissionRepository->find($idDateComm);
            if (null === $dateCommission) {
                throw $this->createNotFoundException(sprintf('Date de commission #%d introuvable', $idDateComm));
            }

            /** @var string|null $first */
            $first = $request->request->get('first');

            return $this->render('calendrier_des_commissions/dialog/_date_edit.html.twig', [
                'dateCommDetail' => $dateCommission,
                'first' => $first,
            ]);
        }

        if ('annule_dateCom' === $do) {
            $dateCommission = $this->dateCommissionRepository->find($idDateComm);
            if (null === $dateCommission) {
                throw $this->createNotFoundException(sprintf('Date de commission #%d introuvable', $idDateComm));
            }

            /** @var string|null $first */
            $first = $request->request->get('first');

            return $this->render('calendrier_des_commissions/dialog/_date_display.html.twig', [
                'updateDateComm' => $dateCommission,
                'dateFormatee' => $this->formatDateFr($dateCommission->getDate() ?? new \DateTime()),
                'first' => $first,
            ]);
        }

        if ('valid_dateCom' === $do) {
            /** @var string $date */
            $date = $request->request->get('date', '');
            /** @var string $hd */
            $hd = $request->request->get('hd', '');
            /** @var string $hf */
            $hf = $request->request->get('hf', '');
            /** @var string|null $first */
            $first = $request->request->get('first');

            $dateCommission = $this->calendarService->updateDateCommission($idDateComm, $date, $hd, $hf);

            return $this->render('calendrier_des_commissions/dialog/_date_display.html.twig', [
                'updateDateComm' => $dateCommission,
                'dateFormatee' => $this->formatDateFr($dateCommission->getDate() ?? new \DateTime()),
                'first' => $first,
            ]);
        }

        if ('supp_dateCom' === $do) {
            $deleted = $this->calendarService->deleteLinkedDate($idDateComm);

            // Vide = date maître (impossible à supprimer), non-vide = suppression réussie.
            return new Response($deleted ? 'ok' : '');
        }

        if ('addDateN' === $do) {
            /** @var string $dateStr */
            $dateStr = $request->request->get('date', '');
            $date = \DateTime::createFromFormat('d/m/Y', $dateStr);
            if (false === $date) {
                return new Response('Erreur format date');
            }

            $dateAjoutee = $this->formatDateFr($date);
            $dateAjouteeInput = $date->format('Y-m-d');

            return $this->render('calendrier_des_commissions/dialog/_date_new.html.twig', [
                'dateAjoutee' => $dateAjoutee,
                'dateAjouteeInput' => $dateAjouteeInput,
            ]);
        }

        if ('addDateS' === $do) {
            /** @var string $dateStr */
            $dateStr = $request->request->get('date', '');
            /** @var string $rawIdDateCommLiee */
            $rawIdDateCommLiee = $request->request->get('idDateCommLiee', '0');
            $idDateCommLiee = (int) $rawIdDateCommLiee;

            try {
                $newDate = $this->calendarService->addLinkedDate($idDateCommLiee, $dateStr);
            } catch (\InvalidArgumentException $e) {
                return new Response('<div class="alert alert-danger">'.htmlspecialchars($e->getMessage()).'</div>');
            }

            $date = \DateTime::createFromFormat('d/m/Y', $dateStr);
            $nouvelleDate = false !== $date ? $this->formatDateFr($date) : $dateStr;

            return $this->render('calendrier_des_commissions/dialog/_date_linked.html.twig', [
                'tabNouvelleDate' => $newDate,
                'nouvelleDate' => $nouvelleDate,
            ]);
        }

        if ('makeDefaut' === $do) {
            $this->calendarService->changeMasterDate($idDateComm);

            return new Response('');
        }

        if ('newComm' === $do) {
            /** @var string $rawIdComm */
            $rawIdComm = $request->request->get('idComm', '0');
            $idCommNewComm = (int) $rawIdComm;

            $dateCommissionDto = new CommissionDatesDto();

            $commission = $this->commissionRepository->find($idCommNewComm);
            if (null !== $commission) {
                $dateCommissionDto->setCommission($commission);
            }

            // Pré-remplissage des dates depuis la sélection FullCalendar (format ISO 8601).
            /** @var string $dateDStr */
            $dateDStr = $request->request->get('dateD', '');
            /** @var string $dateFStr */
            $dateFStr = $request->request->get('dateF', '');

            try {
                if ('' !== $dateDStr) {
                    $dateStart = $this->calendarService->parseJavaScriptDate($dateDStr);
                    $dateCommissionDto->addDate(new DateCommissionItemDto($dateStart));

                    if ('' !== $dateFStr) {
                        $dateEnd = $this->calendarService->parseJavaScriptDate($dateFStr);
                        $numberOfDays = $dateStart->diff($dateEnd)->days;
                        if (is_int($numberOfDays)) {
                            for ($i = 1; $i <= $numberOfDays; ++$i) {
                                $date = clone $dateStart;
                                $date->modify(sprintf('+%d days', $i));
                                $dateCommissionDto->addDate(new DateCommissionItemDto($date));
                            }
                        }
                    }
                }
            } catch (\InvalidArgumentException $e) {
                // Date invalide ou absente : on laisse le formulaire vide
            }

            $form = $this->createForm(CommissionDatesType::class, $dateCommissionDto, [
                'action' => $this->generateUrl('calendrier_des_commissions_creer_date'),
            ]);

            return $this->render('calendrier_des_commissions/form_date.html.twig', [
                'form' => $form->createView(),
                'show_footer' => false,
            ]);
        }

        return new Response('Erreur mauvais choix');
    }

    /**
     * Affiche la boîte de dialogue de confirmation de suppression d'une date.
     *
     * @Route("/commissions/calendrier/alertsuppression", name="calendrier_des_commissions_alertsuppression", methods={"POST"}, options={"expose"=true})
     */
    public function alertSuppression(Request $request): Response
    {
        /** @var string $commissionId */
        $commissionId = $request->request->get('commissionId', '');
        /** @var string $dateCommission */
        $dateCommission = $request->request->get('dateCommission', '');

        return $this->render('calendrier_des_commissions/alertsuppression.html.twig', [
            'commissionId' => $commissionId,
            'dateCommission' => $dateCommission,
        ]);
    }

    /**
     * Supprime définitivement une date de commission et ses données liées.
     *
     * @Route("/commissions/calendrier/validsuppression", name="calendrier_des_commissions_validsuppression", methods={"POST"}, options={"expose"=true})
     */
    public function validSuppression(Request $request): Response
    {
        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('calendrier_commissions_valid_suppression', $csrfToken)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        /** @var string $rawIdDateCommission */
        $rawIdDateCommission = $request->request->get('dateCommission', '0');
        $idDateCommission = (int) $rawIdDateCommission;

        $conn = $this->entityManager->getConnection();

        $listeDossiersAffect = $this->dateCommissionRepository->findDossierAffectForCommission($idDateCommission);
        $listeDossierNonAffect = $this->dateCommissionRepository->findDossierNonAffectForCommission($idDateCommission);
        $listeDossiers = array_merge($listeDossiersAffect, $listeDossierNonAffect);

        foreach ($listeDossiers as $dossier) {
            $conn->executeStatement(
                'UPDATE dossier SET DATECOMM_DOSSIER = NULL WHERE ID_DOSSIER = :id',
                ['id' => is_numeric($dossier['ID_DOSSIER']) ? (int) $dossier['ID_DOSSIER'] : 0]
            );
        }

        $conn->executeStatement(
            'DELETE FROM dossieraffectation WHERE ID_DATECOMMISSION_AFFECT = :id',
            ['id' => $idDateCommission]
        );

        $dateCommission = $this->dateCommissionRepository->find($idDateCommission);

        if (null !== $dateCommission) {
            $piecesJointes = $dateCommission->getPiecesJointes()->toArray();
            $filesystem = new Filesystem();

            foreach ($piecesJointes as $pj) {
                $path = $this->dataStore->getFilePath($pj, 'dateCommission', $idDateCommission);
                if ('' !== $path && $filesystem->exists($path)) {
                    try {
                        $filesystem->remove($path);
                    } catch (IOException $e) {
                        // Fichier non supprimable (permissions) : on continue la suppression de la commission
                    }
                }
            }

            $this->entityManager->remove($dateCommission);
            $this->entityManager->flush();
        }

        return new Response('');
    }

    /**
     * Récupère les événements du calendrier des commissions.
     *
     * @Route("/commissions/calendrier/recupevenement", name="calendrier_des_commissions_recuperer_evenements", methods={"GET"}, options={"expose"=true})
     */
    public function getCalendarEvents(Request $request): JsonResponse
    {
        /** @var string|null $start */
        $start = $request->query->get('start');
        /** @var string|null $end */
        $end = $request->query->get('end');
        /** @var string|null $idCommission */
        $idCommission = $request->query->get('idComm');
        /** @var string|null $type */
        $type = $request->query->get('type');

        if (null === $start || null === $end) {
            return $this->json(['items' => []]);
        }

        $dateDebut = $this->calendarService->parseTimestamp($start);
        $dateFin = $this->calendarService->parseTimestamp($end);

        if (null === $dateDebut || null === $dateFin) {
            return $this->json(['items' => []]);
        }

        $events = $this->calendarService->getEvents(
            $dateDebut,
            $dateFin,
            null !== $idCommission && '' !== $idCommission ? (int) $idCommission : null,
            null !== $type && '' !== $type ? (int) $type : null
        );

        $items = array_map(
            function (CalendarEventDto $event): array {
                return $event->toArray();
            },
            $events
        );

        return $this->json(['items' => $items]);
    }

    /**
     * @todo: Quand tout sera migré sur cette partie, passer l'idDate en tant que paramètre d'URL.
     * exemple: /calendrier-des-commissions/<date>/recuperer-dates-liees qui donnera le paramètre DateCommission $dateCommission.
     *
     * Récupère les dates de commission liées à une date donnée.
     *
     * @Route("/commissions/calendrier/recuperer-dates-liees", name="calendrier_des_commissions_recuperer_dates_liees", methods={"GET"}, options={"expose"=true})
     */
    public function getLinkedDates(Request $request): JsonResponse
    {
        $idDate = filter_var($request->query->get('idDate'), FILTER_VALIDATE_INT);

        if (false === $idDate) {
            return $this->json([]);
        }

        $dateCommission = $this->dateCommissionRepository->find($idDate);

        if (null === $dateCommission) {
            return $this->json([]);
        }

        $datesLiees = $this->dateCommissionRepository->findDatesLiees($dateCommission);

        $dtos = array_map([DateCommissionLieeDto::class, 'fromEntity'], $datesLiees);

        $items = array_map(
            function (DateCommissionLieeDto $dto): array {
                return $dto->toArray();
            },
            $dtos
        );

        return $this->json($items);
    }

    /**
     * Modifie l'heure de fin d'une date de commission.
     *
     * @Route("/commissions/calendrier/redimensionner-date", name="calendrier_des_commissions_redimensionner_date", methods={"POST"}, options={"expose"=true})
     */
    public function resizeCommissionDate(Request $request): JsonResponse
    {
        try {
            $idComm = filter_var($request->request->get('idComm'), FILTER_VALIDATE_INT);
            /** @var string|null $fin */
            $fin = $request->request->get('fin');

            if (false === $idComm || null === $fin) {
                return $this->json([
                    'success' => false,
                    'error' => 'Paramètres manquants',
                ], 400);
            }

            $heureFin = $this->calendarService->parseJavaScriptDateToTime($fin);

            $this->calendarService->updateHeureFin($idComm, $heureFin);

            return $this->json([
                'success' => true,
                'message' => 'L\'événement a bien été modifié',
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la modification de l\'événement',
            ], 500);
        }
    }

    /**
     * Déplace une date de commission (modifie la date et les heures).
     *
     * @Route("/commissions/calendrier/deplacer-date", name="calendrier_des_commissions_deplacer_date", methods={"POST"}, options={"expose"=true})
     */
    public function moveCommissionDate(Request $request): JsonResponse
    {
        try {
            $idComm = filter_var($request->request->get('idComm'), FILTER_VALIDATE_INT);
            /** @var string|null $debut */
            $debut = $request->request->get('debut');

            if (false === $idComm || null === $debut) {
                return $this->json([
                    'success' => false,
                    'error' => 'Paramètres manquants',
                ], 400);
            }

            // Parse de la date (format ISO 8601 JavaScript)
            $nouvelleDate = $this->calendarService->parseJavaScriptDate($debut);

            // Parse des heures optionnelles
            $nouvelleHeureDebut = null;
            /** @var string|null $debutParam */
            $debutParam = $request->request->get('debut');
            if (null !== $debutParam) {
                $nouvelleHeureDebut = $this->calendarService->parseJavaScriptDateToTime($debutParam);
            }

            $nouvelleHeureFin = null;
            /** @var string|null $finParam */
            $finParam = $request->request->get('fin');
            if (null !== $finParam && '' !== $finParam) {
                $nouvelleHeureFin = $this->calendarService->parseJavaScriptDateToTime($finParam);
            }

            $this->calendarService->deplacerDateCommission(
                $idComm,
                $nouvelleDate,
                $nouvelleHeureDebut,
                $nouvelleHeureFin
            );

            return $this->json([
                'success' => true,
                'message' => 'L\'événement a bien été déplacé',
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors du déplacement de l\'événement',
            ], 500);
        }
    }

    /**
     * @Route("/commissions/calendrier/creer-date", name="calendrier_des_commissions_creer_date", methods={"GET", "POST"}, options={"expose"=true})
     */
    public function creerDate(
        Request $request
    ): Response {
        $dateCommissionDto = new CommissionDatesDto();

        // Hydratation du DTO via l'événement FullCalendar et les données du dossier.
        if ($request->isMethod(Request::METHOD_GET)) {
            $dateCommissionDto = $this->calendarService->createDatesDtoFromRequest($request);
        }

        $form = $this->createForm(CommissionDatesType::class, $dateCommissionDto, [
            'action' => $this->generateUrl('calendrier_des_commissions_creer_date'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'error' => "La requête n'est pas correcte",
                ]);
            }

            /** @var CommissionDatesDto $data */
            $data = $form->getData();

            $datesCommission = $this->commissionDatesTransformer->transformToEntities($data);
            try {
                $idAffectation = $this->calendarService->sauvegarderDatesCommission($datesCommission, $data->isWithPeriodicite());
            } catch (EntityNotFoundException $entityNotFoundException) {
                return $this->json([
                    'success' => false,
                    'error' => "La date principale n'a pas pu être récupérée correctement",
                ]);
            }

            // Retourne JSON pour FullCalendar (requête Ajax)
            return $this->json([
                'success' => true,
                'message' => 'La/Les date(s) ont été créée(s) avec succès',
                'idAffectation' => $idAffectation,
            ]);
        }

        return $this->render('calendrier_des_commissions/form_date.html.twig', [
            'form' => $form->createView(),
            // Par défaut : pas de footer si requête AJAX (calendrier principal gère ses propres boutons via jQuery UI dialog).
            // Le contexte dossier passe show_footer=1 pour afficher les boutons dans la modale Bootstrap.
            'show_footer' => (bool) $request->query->get('show_footer', !$request->isXmlHttpRequest()),
        ]);
    }

    /**
     * Page principale de gestion de l'ordre du jour (ODJ).
     * Port direct de gestionodjAction().
     *
     * @Route("/commissions/calendrier/gestion-odj", name="calendrier_des_commissions_gestion_odj", methods={"GET"}, options={"expose"=true})
     */
    public function gestionOdj(Request $request): Response
    {
        /** @var string $rawDateCommId */
        $rawDateCommId = $request->query->get('dateCommId', '0');
        $dateCommId = (int) $rawDateCommId;

        $dateCommission = $this->dateCommissionRepository->find($dateCommId);
        if (null === $dateCommission) {
            throw $this->createNotFoundException(sprintf('Date de commission #%d introuvable', $dateCommId));
        }

        // Détermination de la commission principale (port direct : null → id courant)
        if (null === $dateCommission->getDateCommissionLiees()) {
            $commPrincipale = $dateCommId;
        } else {
            $commPrincipale = (int) $dateCommission->getDateCommissionLiees();
        }

        // Dates liées (liste pour tableau navigation)
        $recupCommLiees = $this->dateCommissionRepository->getCommissionsQtypListing($commPrincipale);
        // Pré-formatage de la dateFr pour chaque date liée
        foreach ($recupCommLiees as $idx => $commLiee) {
            /** @var array<string, mixed> $commLieeTyped */
            $commLieeTyped = $commLiee;
            $rawDate = $commLieeTyped['DATE_COMMISSION'];
            $dateStr = is_string($rawDate) ? $rawDate : '';
            $dateLieeObj = \DateTime::createFromFormat('Y-m-d', $dateStr);
            $recupCommLiees[$idx]['dateFr'] = false !== $dateLieeObj ? $this->formatDateFr($dateLieeObj) : $dateStr;
        }

        $commission = $dateCommission->getCommission();
        $commissionType = null !== $commission ? $commission->getType() : null;

        // Dossiers affectés (avec horaire) et non affectés (sans horaire)
        $listeDossiersNonAffect = $this->dateCommissionRepository->findDossierNonAffectForCommission($dateCommId);
        $listeDossiersAffect = $this->dateCommissionRepository->findDossierAffectForCommission($dateCommId);
        $nbDossiers = count($listeDossiersAffect) + count($listeDossiersNonAffect);

        // Enrichissement des dossiers non affectés avec infosEtab, listeDocUrba, preventionnistes
        $listeDossiersNonAffect = $this->enrichDossiers($listeDossiersNonAffect);

        // Formatage de la date principale en français
        $date = $dateCommission->getDate();
        $dateFr = null !== $date ? $this->formatDateFr($date) : '';

        return $this->render('calendrier_des_commissions/gestionodj.html.twig', [
            'dateCommission' => $dateCommission,
            'commission' => $commission,
            'commissionType' => $commissionType,
            'dateFr' => $dateFr,
            'listeDossierNonAffect' => $listeDossiersNonAffect,
            'nbDossiersAffect' => count($listeDossiersAffect),
            'nbDossiers' => $nbDossiers,
            'recupCommLiees' => $recupCommLiees,
        ]);
    }

    /**
     * Retourne les événements FullCalendar JSON pour les dossiers affectés à une date de commission (ODJ).
     * Port direct de recupevenementodjAction().
     *
     * @Route("/commissions/calendrier/recup-evenement-odj", name="calendrier_des_commissions_recup_evenement_odj", methods={"GET"}, options={"expose"=true})
     */
    public function recupEvenementOdj(Request $request): JsonResponse
    {
        /** @var string $rawStart */
        $rawStart = $request->query->get('start', '0');
        /** @var string $rawEnd */
        $rawEnd = $request->query->get('end', '0');
        /** @var string $rawDateCommId */
        $rawDateCommId = $request->query->get('dateCommId', '0');
        $dateCommId = (int) $rawDateCommId;

        // Conversion ms → sec → date 'Y-m-d' (port de Zend_Date::TIMESTAMP + format YEAR-MONTH-DAY)
        $dateDebut = date('Y-m-d', (int) substr($rawStart, 0, -3));
        $dateFin = date('Y-m-d', (int) substr($rawEnd, 0, -3));

        $listeDossiersAffect = $this->dateCommissionRepository->findDossierAffectForCommission($dateCommId);

        $conn = $this->entityManager->getConnection();

        // 1er passage : enrichissement établissement + doc urba ; unset si aucun établissement (port direct)
        foreach ($listeDossiersAffect as $val => $ue) {
            /** @var array<string, mixed> $ueTyped */
            $ueTyped = $ue;
            $idDossier = is_numeric($ueTyped['ID_DOSSIER']) ? (int) $ueTyped['ID_DOSSIER'] : 0;

            /** @var array<int, array<string, mixed>> $listeEtab */
            $listeEtab = $conn->fetchAllAssociative(
                'SELECT ed.ID_ETABLISSEMENT FROM etablissementdossier ed
                 JOIN etablissement e ON ed.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                 WHERE ed.ID_DOSSIER = :id AND e.DATESUPPRESSION_ETABLISSEMENT IS NULL LIMIT 1',
                ['id' => $idDossier]
            );

            if ([] !== $listeEtab) {
                $idEtab = is_numeric($listeEtab[0]['ID_ETABLISSEMENT']) ? (int) $listeEtab[0]['ID_ETABLISSEMENT'] : 0;
                $listeDossiersAffect[$val]['infosEtab'] = $this->loadEtablissementInfos($idEtab);
                $listeDossiersAffect[$val]['listeDocUrba'] = $conn->fetchAllAssociative(
                    'SELECT NUM_DOCURBA FROM dossierdocurba WHERE ID_DOSSIER = :id',
                    ['id' => $idDossier]
                );
            } else {
                unset($listeDossiersAffect[$val]);
            }
        }

        // 2e passage : construction des événements FullCalendar
        $color = null;
        $items = [];
        foreach ($listeDossiersAffect as $dossierAffect) {
            /** @var array<string, mixed> $dossierAffectTyped */
            $dossierAffectTyped = $dossierAffect;
            /** @var array<string, mixed> $infosEtab */
            $infosEtab = isset($dossierAffectTyped['infosEtab']) ? (array) $dossierAffectTyped['infosEtab'] : [];
            /** @var array<string, mixed> $informations */
            $informations = isset($infosEtab['informations']) ? (array) $infosEtab['informations'] : [];
            /** @var array<int, array<string, mixed>> $adresses */
            $adresses = isset($infosEtab['adresses']) ? (array) $infosEtab['adresses'] : [];
            /** @var array<int, array<string, mixed>> $parents */
            $parents = isset($infosEtab['parents']) ? (array) $infosEtab['parents'] : [];

            // Extraction typée des champs scalaires
            $idDossier = is_numeric($dossierAffectTyped['ID_DOSSIER']) ? (int) $dossierAffectTyped['ID_DOSSIER'] : 0;
            $libelleDossierNature = is_string($dossierAffectTyped['LIBELLE_DOSSIERNATURE']) ? $dossierAffectTyped['LIBELLE_DOSSIERNATURE'] : '';
            $objetDossier = is_string($dossierAffectTyped['OBJET_DOSSIER']) ? $dossierAffectTyped['OBJET_DOSSIER'] : '';
            $heureDebAffect = is_string($dossierAffectTyped['HEURE_DEB_AFFECT']) ? $dossierAffectTyped['HEURE_DEB_AFFECT'] : '';
            $heureFinAffect = is_string($dossierAffectTyped['HEURE_FIN_AFFECT']) ? $dossierAffectTyped['HEURE_FIN_AFFECT'] : '';
            $libelleEtab = isset($informations['LIBELLE_ETABLISSEMENTINFORMATIONS']) && is_string($informations['LIBELLE_ETABLISSEMENTINFORMATIONS'])
                ? $informations['LIBELLE_ETABLISSEMENTINFORMATIONS'] : '';

            $affichage = '';
            if (isset($parents[0]['LIBELLE_ETABLISSEMENTINFORMATIONS']) && is_string($parents[0]['LIBELLE_ETABLISSEMENTINFORMATIONS'])) {
                $affichage .= $parents[0]['LIBELLE_ETABLISSEMENTINFORMATIONS'].' - ';
            }

            $affichage .= $libelleEtab;

            $nbAdresse = count($adresses);
            if (0 !== $nbAdresse) {
                $affichage .= ' (';
                foreach ($adresses as $commune) {
                    $affichage .= is_string($commune['LIBELLE_COMMUNE']) ? $commune['LIBELLE_COMMUNE'] : '';
                    if (1 !== $nbAdresse) {
                        $affichage .= ', ';
                    }

                    --$nbAdresse;
                }

                $affichage .= ') ';
            } else {
                $affichage .= ' ( adresse non renseignée )';
            }

            if ('' !== $libelleDossierNature) {
                $affichage .= ' - '.$libelleDossierNature;
            }

            if ('' !== $objetDossier) {
                $affichage .= ' - Objet : '.$objetDossier;
            }

            /** @var array<int, array<string, mixed>> $listeDocUrba */
            $listeDocUrba = isset($dossierAffectTyped['listeDocUrba']) ? (array) $dossierAffectTyped['listeDocUrba'] : [];
            if (count($listeDocUrba) > 0) {
                $affichage .= ' - Doc urbanisme : ';
                foreach ($listeDocUrba as $docUrba) {
                    $affichage .= (is_string($docUrba['NUM_DOCURBA']) ? $docUrba['NUM_DOCURBA'] : '').' . ';
                }
            }

            /** @var array<int, array<string, mixed>> $preventionnistes */
            $preventionnistes = $conn->fetchAllAssociative(
                'SELECT ui.NOM_UTILISATEURINFORMATIONS, ui.PRENOM_UTILISATEURINFORMATIONS
                 FROM dossierpreventionniste dp
                 JOIN utilisateur u ON dp.ID_PREVENTIONNISTE = u.ID_UTILISATEUR
                 JOIN utilisateurinformations ui ON u.ID_UTILISATEURINFORMATIONS = ui.ID_UTILISATEURINFORMATIONS
                 WHERE dp.ID_DOSSIER = :id',
                ['id' => $idDossier]
            );

            if ([] !== $preventionnistes) {
                $nom0 = is_string($preventionnistes[0]['NOM_UTILISATEURINFORMATIONS']) ? $preventionnistes[0]['NOM_UTILISATEURINFORMATIONS'] : '';
                $prenom0 = is_string($preventionnistes[0]['PRENOM_UTILISATEURINFORMATIONS']) ? $preventionnistes[0]['PRENOM_UTILISATEURINFORMATIONS'] : '';
                $affichage .= ' ('.$nom0.' '.$prenom0.')';
            }

            if (0 == $dossierAffectTyped['VERROU_DOSSIER']) {
                $color = '#e2a420';
            } elseif (1 == $dossierAffectTyped['VERROU_DOSSIER']) {
                $color = '#83bff6';
            }

            $items[] = [
                'id' => $idDossier,
                'url' => $this->generateUrl('dossier_index', ['id' => $idDossier]),
                'title' => '   '.$affichage,
                'start' => $dateDebut.' '.$heureDebAffect,
                'end' => $dateFin.' '.$heureFinAffect,
                'backgroundColor' => $color,
                'allDay' => false,
            ];
        }

        return $this->json(['items' => $items]);
    }

    /**
     * Affecte un dossier à un créneau horaire (drop depuis le panneau latéral vers le calendrier).
     * Port direct de affectedossodjAction().
     *
     * @Route("/commissions/calendrier/affecte-doss-odj", name="calendrier_des_commissions_affecte_doss_odj", methods={"POST"}, options={"expose"=true})
     */
    public function affecteOdj(Request $request): JsonResponse
    {
        try {
            /** @var string $rawDateCommId */
            $rawDateCommId = $request->request->get('dateCommId', '0');
            /** @var string $rawIdDossier */
            $rawIdDossier = $request->request->get('idDossier', '0');
            /** @var string $datadebut */
            $datadebut = $request->request->get('datadebut', '');

            $dateCommId = (int) $rawDateCommId;
            $idDossier = (int) $rawIdDossier;

            $dossAffect = $this->dossierAffectationRepository->findOneBy([
                'dateCommission' => $dateCommId,
                'dossier' => $idDossier,
            ]);

            if (null === $dossAffect) {
                throw new \RuntimeException(sprintf('Affectation introuvable : dateComm=%d dossier=%d', $dateCommId, $idDossier));
            }

            $dateAttribDoss = new \DateTime($datadebut);
            $dossAffect->setHeureDebut($dateAttribDoss);

            $dateAttribDossPlus5 = clone $dateAttribDoss;
            $dateAttribDossPlus5->modify('+5 minutes');
            $dossAffect->setHeureFin($dateAttribDossPlus5);

            $this->entityManager->persist($dossAffect);
            $this->entityManager->flush();

            $dossierEntity = $dossAffect->getDossier();
            $verrou = null !== $dossierEntity ? (int) $dossierEntity->hasVerrou() : 0;

            $listeDossiersAffect = $this->dateCommissionRepository->findDossierAffectForCommission($dateCommId);

            return $this->json([
                'verrou' => $verrou,
                'countAffect' => count($listeDossiersAffect),
            ]);
        } catch (\Exception $exception) {
            $this->addFlash('danger', "Erreur lors de l'affectation du dossier : ".$exception->getMessage());

            return $this->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Redimensionne un événement ODJ (mise à jour de l'heure de fin uniquement).
     * Port direct de resizeodjAction().
     *
     * @Route("/commissions/calendrier/resize-odj", name="calendrier_des_commissions_resize_odj", methods={"POST"})
     */
    public function resizeOdj(Request $request): Response
    {
        try {
            /** @var string $rawDateCommId */
            $rawDateCommId = $request->request->get('dateCommId', '0');
            /** @var string $rawIdDossier */
            $rawIdDossier = $request->request->get('idDossier', '0');
            /** @var string $dateFin */
            $dateFin = $request->request->get('dateFin', '');

            $dateCommId = (int) $rawDateCommId;
            $idDossier = (int) $rawIdDossier;

            $heureFin = new \DateTime($dateFin);

            $dossierAffectation = $this->dossierAffectationRepository->findOneBy([
                'dateCommission' => $dateCommId,
                'dossier' => $idDossier,
            ]);

            if (null === $dossierAffectation) {
                throw new \RuntimeException(sprintf('Affectation introuvable : dateComm=%d dossier=%d', $dateCommId, $idDossier));
            }

            $dossierAffectation->setHeureFin($heureFin);
            $this->entityManager->persist($dossierAffectation);
            $this->entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => "L'événement a bien été modifié."]);
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false, 'message' => "Erreur lors de la modification de l'événement : ".$exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Déplace un événement ODJ (mise à jour heure de début et de fin).
     * Port direct de dropodjAction().
     *
     * @Route("/commissions/calendrier/drop-odj", name="calendrier_des_commissions_drop_odj", methods={"POST"})
     */
    public function dropOdj(Request $request): Response
    {
        try {
            /** @var string $rawDateCommId */
            $rawDateCommId = $request->request->get('dateCommId', '0');
            /** @var string $rawIdDossier */
            $rawIdDossier = $request->request->get('idDossier', '0');
            /** @var string $heureDebutParam */
            $heureDebutParam = $request->request->get('heureDebut', '');
            /** @var string $heureFinParam */
            $heureFinParam = $request->request->get('heureFin', '');

            $dateCommId = (int) $rawDateCommId;
            $idDossier = (int) $rawIdDossier;

            $heureDeb = new \DateTime($heureDebutParam);
            $heureFin = new \DateTime($heureFinParam);

            $dossierAffectation = $this->dossierAffectationRepository->findOneBy([
                'dateCommission' => $dateCommId,
                'dossier' => $idDossier,
            ]);

            if (null === $dossierAffectation) {
                throw new \RuntimeException(sprintf('Affectation introuvable : dateComm=%d dossier=%d', $dateCommId, $idDossier));
            }

            $dossierAffectation->setHeureDebut($heureDeb);
            $dossierAffectation->setHeureFin($heureFin);
            $this->entityManager->persist($dossierAffectation);
            $this->entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => 'Événement déplacé avec succès.']);
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur inattendue : '.$exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Bascule entre mode horaire (GESTION_HEURES=1) et séquentiel (GESTION_HEURES=0).
     * Port direct de gestionheuresAction().
     *
     * @Route("/commissions/calendrier/gestion-heures", name="calendrier_des_commissions_gestion_heures", methods={"POST"})
     */
    public function gestionHeures(Request $request): Response
    {
        try {
            /** @var string $rawDateCommId */
            $rawDateCommId = $request->request->get('dateCommId', '0');
            /** @var string $gestionHeureParam */
            $gestionHeureParam = $request->request->get('gestionHeure', '');

            $dateCommId = (int) $rawDateCommId;

            $gestionHeure = null;
            if ('non' === $gestionHeureParam) {
                $gestionHeure = 0;
            } elseif ('oui' === $gestionHeureParam) {
                $gestionHeure = 1;
            }

            $dateCommission = $this->dateCommissionRepository->find($dateCommId);
            if (null === $dateCommission) {
                throw new \RuntimeException(sprintf('Date de commission #%d introuvable', $dateCommId));
            }

            $dateCommission->setGestionHeures(1 === $gestionHeure);
            $this->entityManager->persist($dateCommission);
            $this->entityManager->flush();

            $listeDossiersConcernes = $this->dossierAffectationRepository->findAllForCommission($dateCommId);

            if (0 === $gestionHeure) {
                // Mode séquentiel : reset heures, numérotation 0,1,2,...
                $nbDossier = 0;
                foreach ($listeDossiersConcernes as $val) {
                    /** @var array<string, mixed> $valTyped */
                    $valTyped = $val;
                    $idDossierAffect = is_numeric($valTyped['ID_DOSSIER_AFFECT']) ? (int) $valTyped['ID_DOSSIER_AFFECT'] : 0;
                    if (0 !== $idDossierAffect) {
                        $dossierAffect = $this->dossierAffectationRepository->findOneBy([
                            'dateCommission' => $dateCommId,
                            'dossier' => $idDossierAffect,
                        ]);
                        if (null !== $dossierAffect) {
                            $dossierAffect->setHeureDebut(null);
                            $dossierAffect->setHeureFin(null);
                            $dossierAffect->setNumero($nbDossier);
                            $this->entityManager->persist($dossierAffect);
                        }
                    }

                    ++$nbDossier;
                }
            } else {
                // Mode horaire : reset heures, NUM_DOSSIER = 0 pour tous
                foreach ($listeDossiersConcernes as $val) {
                    /** @var array<string, mixed> $valTyped */
                    $valTyped = $val;
                    $idDossierAffect = is_numeric($valTyped['ID_DOSSIER_AFFECT']) ? (int) $valTyped['ID_DOSSIER_AFFECT'] : 0;
                    $dossierAffect = $this->dossierAffectationRepository->findOneBy([
                        'dateCommission' => $dateCommId,
                        'dossier' => $idDossierAffect,
                    ]);
                    if (null !== $dossierAffect) {
                        $dossierAffect->setHeureDebut(null);
                        $dossierAffect->setHeureFin(null);
                        $dossierAffect->setNumero(0);
                        $this->entityManager->persist($dossierAffect);
                    }
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', "La modification de l'événement a bien été enregistrée");
        } catch (\Exception $exception) {
            $this->addFlash('danger', "Erreur lors de la modification de l'événement : ".$exception->getMessage());
        }

        return new Response('');
    }

    /**
     * Réordonnancement séquentiel des dossiers par drag & drop.
     * Port direct de changementordreAction().
     *
     * @Route("/commissions/calendrier/changement-ordre", name="calendrier_des_commissions_changement_ordre", methods={"POST"})
     */
    public function changementOrdre(Request $request): Response
    {
        try {
            /** @var string $rawDateCommId */
            $rawDateCommId = $request->request->get('dateCommId', '0');
            /** @var string $stringUpdate */
            $stringUpdate = $request->request->get('ordreDossier', '');

            $dateCommId = (int) $rawDateCommId;
            $dossierId = explode(',', $stringUpdate);

            $numDossier = 0;
            foreach ($dossierId as $idDossier) {
                $updateOrdreDossier = $this->dossierAffectationRepository->findOneBy([
                    'dateCommission' => $dateCommId,
                    'dossier' => (int) $idDossier,
                ]);
                if (null !== $updateOrdreDossier) {
                    $updateOrdreDossier->setHeureDebut(null);
                    $updateOrdreDossier->setHeureFin(null);
                    $updateOrdreDossier->setNumero($numDossier);
                    $this->entityManager->persist($updateOrdreDossier);
                }

                ++$numDossier;
            }

            $this->entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => 'Ordre des dossiers mis à jour.']);
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur inattendue : '.$exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Affiche les pièces jointes d'une date de commission dans la modale.
     *
     * @Route("/commissions/calendrier/pieces-jointes/{id}", name="calendrier_des_commissions_pieces_jointes", methods={"GET"}, requirements={"id"="\d+"}, options={"expose"=true})
     */
    public function piecesJointes(DateCommission $dateCommission): Response
    {
        $piecesJointes = array_values(array_filter(
            $dateCommission->getPiecesJointes()->toArray(),
            function (PieceJointe $pj) use ($dateCommission): bool {
                $filePath = $this->dataStore->getFilePath($pj, 'dateCommission', (int) $dateCommission->getId());

                return is_readable($filePath);
            }
        ));

        return $this->render('calendrier_des_commissions/pieces_jointes.html.twig', [
            'dateCommission' => $dateCommission,
            'piecesJointes' => $piecesJointes,
        ]);
    }

    /**
     * Retourne le formulaire d'ajout de pièce jointe (chargé en AJAX dans la modale).
     *
     * @Route("/commissions/calendrier/pieces-jointes/{id}/formulaire", name="calendrier_des_commissions_pj_formulaire", methods={"GET"}, requirements={"id"="\d+"}, options={"expose"=true})
     */
    public function piecesJointesForm(DateCommission $dateCommission): Response
    {
        return $this->render('calendrier_des_commissions/pieces_jointes_form.html.twig', [
            'dateCommission' => $dateCommission,
        ]);
    }

    /**
     * Upload d'une pièce jointe pour une date de commission.
     * Retourne un JSON avec le HTML de la nouvelle ligne de tableau.
     *
     * @Route("/commissions/calendrier/pieces-jointes/{id}/ajouter", name="calendrier_des_commissions_pj_ajouter", methods={"POST"}, requirements={"id"="\d+"}, options={"expose"=true})
     */
    public function piecesJointesAdd(Request $request, DateCommission $dateCommission): JsonResponse
    {
        try {
            /** @var UploadedFile|null $fichier */
            $fichier = $request->files->get('fichier');
            if (!$fichier instanceof UploadedFile) {
                return new JsonResponse(['success' => false, 'message' => 'Aucun fichier reçu.'], Response::HTTP_BAD_REQUEST);
            }

            $extensionRaw = strrchr($fichier->getClientOriginalName(), '.');
            $extension = strtolower(false !== $extensionRaw ? $extensionRaw : '');
            if ('' === $extension || in_array($extension, ['.php', '.php4', '.php5', '.sh', '.ksh', '.csh'], true)) {
                return new JsonResponse(['success' => false, 'message' => "Ce type de fichier n'est pas autorisé."], Response::HTTP_BAD_REQUEST);
            }

            /** @var string $rawNom */
            $rawNom = $request->request->get('nomFichier', '');
            $nom = '' !== $rawNom ? $rawNom : substr((string) $fichier->getClientOriginalName(), 0, -strlen($extension));
            /** @var string $description */
            $description = $request->request->get('descriptionFichier', '');

            $pj = new PieceJointe();
            $pj->setExtension($extension);
            $pj->setNom($nom);
            $pj->setDescription('' !== $description ? $description : null);
            $pj->setDate(new \DateTime());

            $this->pieceJointeRepository->add($pj, false);

            $filePath = $this->dataStore->getFilePath($pj, 'dateCommission', (int) $dateCommission->getId(), true);
            $fichier->move(dirname($filePath), basename($filePath));

            $dateCommission->addPiecesJointe($pj);
            $this->entityManager->flush();

            $rowHtml = $this->renderView('calendrier_des_commissions/_piece_jointe_row.html.twig', [
                'pj' => $pj,
                'dateCommission' => $dateCommission,
            ]);

            return new JsonResponse(['success' => true, 'html' => $rowHtml]);
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false, 'message' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprime une pièce jointe d'une date de commission.
     *
     * @Route("/commissions/calendrier/pieces-jointes/{id}/supprimer/{idPj}", name="calendrier_des_commissions_pj_supprimer", methods={"DELETE", "POST"}, requirements={"id"="\d+", "idPj"="\d+"}, options={"expose"=true})
     */
    public function piecesJointesDelete(DateCommission $dateCommission, int $idPj): JsonResponse
    {
        try {
            $pj = $this->pieceJointeRepository->find($idPj);
            if (null === $pj) {
                return new JsonResponse(['success' => false, 'message' => 'Pièce jointe introuvable.'], Response::HTTP_NOT_FOUND);
            }

            if (!$dateCommission->getPiecesJointes()->contains($pj)) {
                return new JsonResponse(['success' => false, 'message' => 'Cette pièce jointe n\'appartient pas à cette date de commission.'], Response::HTTP_FORBIDDEN);
            }

            $filePath = $this->dataStore->getFilePath($pj, 'dateCommission', (int) $dateCommission->getId());
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $dateCommission->removePiecesJointe($pj);
            $this->pieceJointeRepository->remove($pj);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false, 'message' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Formate une date en libellé français court (ex: "Lundi 12 Jan 2026").
     * Port de Zend_Date::get(WEEKDAY DAY_SHORT MONTH_NAME_SHORT YEAR, 'fr').
     */
    private function formatDateFr(\DateTimeInterface $date): string
    {
        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $months = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

        return $days[(int) $date->format('w')].' '.$date->format('d').' '.$months[(int) $date->format('n')].' '.$date->format('Y');
    }

    /**
     * Enrichit un tableau de dossiers bruts avec infosEtab, listeDocUrba et preventionnistes.
     * Port du bloc d'enrichissement inline de gestionodjAction().
     *
     * @param array<int, array<string, mixed>> $dossiers
     *
     * @return array<int, array<string, mixed>>
     */
    private function enrichDossiers(array $dossiers): array
    {
        $conn = $this->entityManager->getConnection();

        foreach ($dossiers as $val => $ue) {
            /** @var array<string, mixed> $ueTyped */
            $ueTyped = $ue;
            $idDossier = is_numeric($ueTyped['ID_DOSSIER']) ? (int) $ueTyped['ID_DOSSIER'] : 0;

            /** @var array<int, array<string, mixed>> $listeEtab */
            $listeEtab = $conn->fetchAllAssociative(
                'SELECT ed.ID_ETABLISSEMENT FROM etablissementdossier ed
                 JOIN etablissement e ON ed.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                 WHERE ed.ID_DOSSIER = :id AND e.DATESUPPRESSION_ETABLISSEMENT IS NULL LIMIT 1',
                ['id' => $idDossier]
            );

            if ([] !== $listeEtab) {
                $idEtab = is_numeric($listeEtab[0]['ID_ETABLISSEMENT']) ? (int) $listeEtab[0]['ID_ETABLISSEMENT'] : 0;
                $dossiers[$val]['infosEtab'] = $this->loadEtablissementInfos($idEtab);
                $dossiers[$val]['listeDocUrba'] = $conn->fetchAllAssociative(
                    'SELECT NUM_DOCURBA FROM dossierdocurba WHERE ID_DOSSIER = :id',
                    ['id' => $idDossier]
                );
                $dossiers[$val]['preventionnistes'] = $conn->fetchAllAssociative(
                    'SELECT ui.NOM_UTILISATEURINFORMATIONS, ui.PRENOM_UTILISATEURINFORMATIONS
                     FROM dossierpreventionniste dp
                     JOIN utilisateur u ON dp.ID_PREVENTIONNISTE = u.ID_UTILISATEUR
                     JOIN utilisateurinformations ui ON u.ID_UTILISATEURINFORMATIONS = ui.ID_UTILISATEURINFORMATIONS
                     WHERE dp.ID_DOSSIER = :id',
                    ['id' => $idDossier]
                );
            }
        }

        return $dossiers;
    }

    /**
     * Charge les informations d'affichage d'un établissement (informations, adresses, parents).
     * Équivalent partiel de Service_Etablissement::get() pour les besoins ODJ.
     *
     * @return array<string, mixed>
     */
    private function loadEtablissementInfos(int $idEtab): array
    {
        $conn = $this->entityManager->getConnection();

        // Libellé principal (dernière fiche d'informations)
        $informations = $conn->fetchAssociative(
            'SELECT ei.LIBELLE_ETABLISSEMENTINFORMATIONS
             FROM etablissementinformations ei
             WHERE ei.ID_ETABLISSEMENT = :id
             ORDER BY ei.DATE_ETABLISSEMENTINFORMATIONS DESC
             LIMIT 1',
            ['id' => $idEtab]
        );

        // Adresses (communes)
        /** @var array<int, array<string, mixed>> $adresses */
        $adresses = $conn->fetchAllAssociative(
            'SELECT c.LIBELLE_COMMUNE
             FROM etablissementadresse ea
             JOIN adressecommune c ON ea.NUMINSEE_COMMUNE = c.NUMINSEE_COMMUNE
             WHERE ea.ID_ETABLISSEMENT = :id',
            ['id' => $idEtab]
        );

        // Parents directs (table etablissementlie : ID_FILS_ETABLISSEMENT = enfant, ID_ETABLISSEMENT = parent)
        /** @var array<int, array<string, mixed>> $parents */
        $parents = $conn->fetchAllAssociative(
            'SELECT ei.LIBELLE_ETABLISSEMENTINFORMATIONS
             FROM etablissementlie el
             JOIN etablissementinformations ei ON el.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
                 AND ei.DATE_ETABLISSEMENTINFORMATIONS = (
                     SELECT MAX(ei2.DATE_ETABLISSEMENTINFORMATIONS)
                     FROM etablissementinformations ei2
                     WHERE ei2.ID_ETABLISSEMENT = el.ID_ETABLISSEMENT
                 )
             WHERE el.ID_FILS_ETABLISSEMENT = :id',
            ['id' => $idEtab]
        );

        return [
            'informations' => false !== $informations ? $informations : ['LIBELLE_ETABLISSEMENTINFORMATIONS' => ''],
            'adresses' => $adresses,
            'parents' => $parents,
        ];
    }
}
