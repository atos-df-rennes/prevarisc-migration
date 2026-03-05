<?php

namespace App\Controller;

use App\Dto\Commission\CalendarEventDto;
use App\Dto\Commission\CommissionDatesDto;
use App\Dto\Commission\DateCommissionLieeDto;
use App\Form\CalendrierDesCommissions\CommissionDatesType;
use App\Repository\DateCommissionRepository;
use App\Service\Commission\CalendarService;
use App\Service\Commission\CommissionDatesTransformer;
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

    public function __construct(CalendarService $calendarService, DateCommissionRepository $dateCommissionRepository, CommissionDatesTransformer $commissionDatesTransformer)
    {
        $this->calendarService = $calendarService;
        $this->dateCommissionRepository = $dateCommissionRepository;
        $this->commissionDatesTransformer = $commissionDatesTransformer;
    }

    /**
     * Récupère les événements du calendrier des commissions.
     *
     * @Route("/calendrier-des-commissions/recupevenement", name="calendrier_des_commissions_recuperer_evenements", methods={"GET"}, options={"expose"=true})
     */
    public function recupevenement(Request $request): JsonResponse
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
    public function recupdateliee(Request $request): JsonResponse
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
    public function resizecommissiondate(Request $request): JsonResponse
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
    public function deplacecommissiondate(Request $request): JsonResponse
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
     * @Route("/calendrier-des-commissions/creer-date", name="calendrier_des_commissions_creer_date", methods={"GET", "POST"}, options={"expose"=true})
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
        ]);
    }
}
