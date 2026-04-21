<?php

namespace App\Controller\Commission;

use App\Dto\Commission\CalendarEventDto;
use App\Dto\Commission\CommissionDatesDto;
use App\Dto\Commission\DateCommissionItemDto;
use App\Dto\Commission\DateCommissionLieeDto;
use App\Entity\Commission;
use App\Entity\Utilisateur;
use App\Form\CalendrierDesCommissions\CommissionDatesType;
use App\Repository\CommissionRepository;
use App\Repository\CommissionTypeEvenementRepository;
use App\Repository\DateCommissionRepository;
use App\Service\Commission\CalendarService;
use App\Service\Commission\CommissionDatesTransformer;
use App\Service\DataStore\DataStore;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// @todo: Une fois la migration terminée, supprimer les préfixes /symfony.
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
     * @var DataStore
     */
    private $dataStore;

    public function __construct(
        CalendarService $calendarService,
        DateCommissionRepository $dateCommissionRepository,
        CommissionDatesTransformer $commissionDatesTransformer,
        CommissionTypeEvenementRepository $commissionTypeEvenementRepository,
        CommissionRepository $commissionRepository,
        EntityManagerInterface $entityManager,
        DataStore $dataStore
    ) {
        $this->calendarService = $calendarService;
        $this->dateCommissionRepository = $dateCommissionRepository;
        $this->commissionDatesTransformer = $commissionDatesTransformer;
        $this->commissionTypeEvenementRepository = $commissionTypeEvenementRepository;
        $this->commissionRepository = $commissionRepository;
        $this->entityManager = $entityManager;
        $this->dataStore = $dataStore;
    }

    /**
     * Page principale du calendrier des commissions.
     *
     * @Route("/symfony/calendrier-des-commissions", name="calendrier_des_commissions_index", methods={"GET"}, options={"expose"=true})
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
     * @Route("/symfony/calendrier-des-commissions/commissionselection", name="calendrier_des_commissions_commissionselection", methods={"GET"}, options={"expose"=true})
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
     * @Route("/symfony/calendrier-des-commissions/dialogcomm", name="calendrier_des_commissions_dialog_edit", methods={"POST"}, options={"expose"=true})
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
                            for ($i = 1; $i < $numberOfDays; ++$i) {
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
     * @Route("/symfony/calendrier-des-commissions/alertsuppression", name="calendrier_des_commissions_alertsuppression", methods={"POST"}, options={"expose"=true})
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
     * @Route("/symfony/calendrier-des-commissions/validsuppression", name="calendrier_des_commissions_validsuppression", methods={"POST"}, options={"expose"=true})
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

            foreach ($piecesJointes as $pj) {
                $path = $this->dataStore->getFilePath($pj, 'dateCommission', $idDateCommission);
                if (file_exists($path)) {
                    @unlink($path);
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
     * @Route("/symfony/calendrier-des-commissions/recupevenement", name="calendrier_des_commissions_recuperer_evenements", methods={"GET"}, options={"expose"=true})
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
     * @Route("/symfony/calendrier-des-commissions/recuperer-dates-liees", name="calendrier_des_commissions_recuperer_dates_liees", methods={"GET"}, options={"expose"=true})
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
     * @Route("/symfony/calendrier-des-commissions/redimensionner-date", name="calendrier_des_commissions_redimensionner_date", methods={"POST"}, options={"expose"=true})
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
     * @Route("/symfony/calendrier-des-commissions/deplacer-date", name="calendrier_des_commissions_deplacer_date", methods={"POST"}, options={"expose"=true})
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
            if (null !== $finParam) {
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
     * @Route("/symfony/calendrier-des-commissions/creer-date", name="calendrier_des_commissions_creer_date", methods={"GET", "POST"}, options={"expose"=true})
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

            if (!$request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'error' => "La requête n'est pas correcte"
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
            'show_footer' => !$request->isXmlHttpRequest(),
        ]);
    }

    /**
     * Formate une date en libellé français court (ex: "Lundi 12 Jan 2026").
     * Port de Zend_Date::get(WEEKDAY DAY_SHORT MONTH_NAME_SHORT YEAR, 'fr').
     */
    private function formatDateFr(\DateTime $date): string
    {
        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $months = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

        return $days[(int) $date->format('w')].' '.$date->format('d').' '.$months[(int) $date->format('n')].' '.$date->format('Y');
    }
}
