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
use App\Utils\Date;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Routes du calendrier des commissions migrées vers Symfony.
// URL principale : /commissions/calendrier (≠ legacy /calendrier-des-commissions).
// Les routes AJAX partagent le même préfixe pour ne pas interférer avec le Zend legacy.
// ODJ → CalendrierDesCommissionsOdjController
// Export ICS → CalendrierDesCommissionsExportController
// Pièces jointes → CalendrierDesCommissionsPiecesJointesController
class CalendrierDesCommissionsController extends AbstractController
{
    public function __construct(private readonly CalendarService $calendarService, private readonly DateCommissionRepository $dateCommissionRepository, private readonly CommissionDatesTransformer $commissionDatesTransformer, private readonly CommissionTypeEvenementRepository $commissionTypeEvenementRepository, private readonly CommissionRepository $commissionRepository, private readonly EntityManagerInterface $entityManager, private readonly DataStore $dataStore, private readonly string $securityKey)
    {
    }

    /**
     * Page principale du calendrier des commissions.
     */
    #[Route(path: '/commissions/calendrier', name: 'calendrier_des_commissions_index', methods: ['GET'], options: ['expose' => true])]
    #[IsGranted('lecture_commission')]
    public function index(Request $request, #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $idComm = null): Response
    {
        $arrayCommissions = $this->commissionRepository->getCommissionsAndTypes();

        $isAdmin = $this->isGranted('gestion_commissions');
        $isViewAll = $this->isGranted('calendar_view_all');

        $user = $this->getUser();
        $userCommissionIds = [];
        if ($user instanceof Utilisateur) {
            $userCommissionIds = array_map(
                fn (Commission $c): int => (int) $c->getId(),
                $user->getCommissions()->toArray()
            );
        }

        $urlWebcal = '';
        if ('' !== $this->securityKey) {
            $userId = ($user instanceof Utilisateur) ? (int) $user->getId() : 0;
            $baseScheme = $request->isSecure() ? 'webcals' : 'webcal';
            $host = $request->getHttpHost();
            // Génère le chemin via le router Symfony (sans commission — le JS l'ajoute pour syncCom)
            $path = $this->generateUrl(
                'api_icalendar_sync',
                ['userid' => $userId, 'key' => $this->securityKey],
                UrlGeneratorInterface::ABSOLUTE_PATH
            );
            $urlWebcal = $baseScheme.'://'.$host.$path;
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
     */
    #[Route(path: '/commissions/calendrier/commissionselection', name: 'calendrier_des_commissions_commissionselection', methods: ['GET'], options: ['expose' => true])]
    #[IsGranted('lecture_commission')]
    public function commissionSelection(#[MapQueryParameter] string $q = ''): JsonResponse
    {
        if ('' === $q) {
            return $this->json([]);
        }

        $conn = $this->entityManager->getConnection();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative(
            'SELECT ID_COMMISSION, LIBELLE_COMMISSION FROM commission WHERE LIBELLE_COMMISSION LIKE :q',
            ['q' => '%'.$q.'%']
        );

        $items = array_map(fn (array $row): array => [
            'id' => $row['ID_COMMISSION'],
            'infos' => $row['LIBELLE_COMMISSION'],
        ], $rows);

        return $this->json($items);
    }

    /**
     * Dialogue de gestion des dates de commission (mega-switch).
     */
    #[Route(path: '/commissions/calendrier/dialogcomm', name: 'calendrier_des_commissions_dialog_edit', methods: ['POST'], options: ['expose' => true])]
    #[IsGranted('ecriture_commission')]
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
                'typeComSelect' => $typeEvenement?->getId(),
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
                'dateFormatee' => Date::formatDateFr($dateCommission->getDate() ?? new \DateTime()),
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
                'dateFormatee' => Date::formatDateFr($dateCommission->getDate() ?? new \DateTime()),
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

            $dateAjoutee = Date::formatDateFr($date);
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
            $nouvelleDate = false !== $date ? Date::formatDateFr($date) : $dateStr;

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
                        for ($i = 1; $i <= $numberOfDays; ++$i) {
                            $date = clone $dateStart;
                            $date->modify(sprintf('+%d days', $i));
                            $dateCommissionDto->addDate(new DateCommissionItemDto($date));
                        }
                    }
                }
            } catch (\InvalidArgumentException) {
                // Date invalide ou absente : on laisse le formulaire vide
            }

            $form = $this->createForm(CommissionDatesType::class, $dateCommissionDto, [
                'action' => $this->generateUrl('calendrier_des_commissions_creer_date'),
            ]);

            return $this->render('calendrier_des_commissions/form_date.html.twig', [
                'form' => $form,
                'show_footer' => false,
            ]);
        }

        return new Response('Erreur mauvais choix');
    }

    /**
     * Affiche la boîte de dialogue de confirmation de suppression d'une date.
     */
    #[Route(path: '/commissions/calendrier/alertsuppression', name: 'calendrier_des_commissions_alertsuppression', methods: ['POST'], options: ['expose' => true])]
    #[IsGranted('ecriture_commission')]
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
     */
    #[Route(path: '/commissions/calendrier/validsuppression', name: 'calendrier_des_commissions_validsuppression', methods: ['POST'], options: ['expose' => true])]
    #[IsGranted('ecriture_commission')]
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
                ['id' => is_numeric($dossier->ID_DOSSIER) ? (int) $dossier->ID_DOSSIER : 0]
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
                    } catch (IOException) {
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
     */
    #[Route(path: '/commissions/calendrier/recupevenement', name: 'calendrier_des_commissions_recuperer_evenements', methods: ['GET'], options: ['expose' => true])]
    #[IsGranted('lecture_commission')]
    public function getCalendarEvents(
        #[MapQueryParameter] ?string $start = null,
        #[MapQueryParameter] ?string $end = null,
        #[MapQueryParameter(name: 'idComm')] ?string $idCommission = null,
        #[MapQueryParameter] ?string $type = null,
    ): JsonResponse {
        if (null === $start || null === $end) {
            return $this->json(['items' => []]);
        }

        $dateDebut = $this->calendarService->parseTimestamp($start);
        $dateFin = $this->calendarService->parseTimestamp($end);

        if (!$dateDebut instanceof \DateTimeInterface || !$dateFin instanceof \DateTimeInterface) {
            return $this->json(['items' => []]);
        }

        $events = $this->calendarService->getEvents(
            $dateDebut,
            $dateFin,
            null !== $idCommission && '' !== $idCommission ? (int) $idCommission : null,
            null !== $type && '' !== $type ? (int) $type : null
        );

        $items = array_map(
            fn (CalendarEventDto $event): array => $event->toArray(),
            $events
        );

        return $this->json(['items' => $items]);
    }

    /**
     * @todo: Quand tout sera migré sur cette partie, passer l'idDate en tant que paramètre d'URL.
     * exemple: /calendrier-des-commissions/<date>/recuperer-dates-liees qui donnera le paramètre DateCommission $dateCommission.
     *
     * Récupère les dates de commission liées à une date donnée.
     */
    #[Route(path: '/commissions/calendrier/recuperer-dates-liees', name: 'calendrier_des_commissions_recuperer_dates_liees', methods: ['GET'], options: ['expose' => true])]
    #[IsGranted('lecture_commission')]
    public function getLinkedDates(#[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $idDate = null): JsonResponse
    {
        if (null === $idDate) {
            return $this->json([]);
        }

        $dateCommission = $this->dateCommissionRepository->find($idDate);

        if (null === $dateCommission) {
            return $this->json([]);
        }

        $datesLiees = $this->dateCommissionRepository->findDatesLiees($dateCommission);

        $dtos = array_map([DateCommissionLieeDto::class, 'fromEntity'], $datesLiees);

        $items = array_map(
            fn (DateCommissionLieeDto $dto): array => $dto->toArray(),
            $dtos
        );

        return $this->json($items);
    }

    /**
     * Modifie l'heure de fin d'une date de commission.
     */
    #[Route(path: '/commissions/calendrier/redimensionner-date', name: 'calendrier_des_commissions_redimensionner_date', methods: ['POST'], options: ['expose' => true])]
    #[IsGranted('lecture_commission')]
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
                'message' => "L'événement a bien été modifié",
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception) {
            return $this->json([
                'success' => false,
                'error' => "Erreur lors de la modification de l'événement",
            ], 500);
        }
    }

    /**
     * Déplace une date de commission (modifie la date et les heures).
     */
    #[Route(path: '/commissions/calendrier/deplacer-date', name: 'calendrier_des_commissions_deplacer_date', methods: ['POST'], options: ['expose' => true])]
    #[IsGranted('ecriture_commission')]
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
                'message' => "L'événement a bien été déplacé",
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception) {
            return $this->json([
                'success' => false,
                'error' => "Erreur lors du déplacement de l'événement",
            ], 500);
        }
    }

    #[Route(path: '/commissions/calendrier/creer-date', name: 'calendrier_des_commissions_creer_date', methods: ['GET', 'POST'], options: ['expose' => true])]
    #[IsGranted('creation_commission')]
    public function creerDate(
        Request $request,
        #[MapQueryParameter(name: 'show_footer', flags: \FILTER_NULL_ON_FAILURE)] ?bool $showFooter = null,
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
            } catch (EntityNotFoundException) {
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
            'form' => $form,
            // Par défaut : pas de footer si requête AJAX (calendrier principal gère ses propres boutons via jQuery UI dialog).
            // Le contexte dossier passe show_footer=1 pour afficher les boutons dans la modale Bootstrap.
            'show_footer' => $showFooter ?? !$request->isXmlHttpRequest(),
        ]);
    }
}
