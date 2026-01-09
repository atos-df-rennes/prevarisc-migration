<?php

namespace App\Controller;

use App\Dto\Commission\CalendarEventDto;
use App\Dto\Commission\DateCommissionLieeDto;
use App\Repository\DateCommissionRepository;
use App\Service\Commission\CalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CalendrierDesCommissionsController extends AbstractController
{
    /**
     * Récupère les événements du calendrier des commissions.
     *
     * @Route("/calendrier-des-commissions/recupevenement", name="calendrier_des_commissions_recuperer_evenements", methods={"GET"})
     */
    public function recupevenement(Request $request, CalendarService $calendarService): JsonResponse
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

        $dateDebut = $calendarService->parseTimestamp($start);
        $dateFin = $calendarService->parseTimestamp($end);

        if (null === $dateDebut || null === $dateFin) {
            return $this->json(['items' => []]);
        }

        $events = $calendarService->getEvents(
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
     * Récupère les dates de commission liées à une date donnée.
     *
     * @Route("/symfony/calendrier-des-commissions/recuperer-dates-liees", name="calendrier_des_commissions_recuperer_dates_liees", methods={"GET"})
     */
    public function recupdateliee(Request $request, DateCommissionRepository $dateCommissionRepository): JsonResponse
    {
        $idDate = filter_var($request->query->get('idDate'), FILTER_VALIDATE_INT);

        if (false === $idDate) {
            return $this->json([]);
        }

        $dateCommission = $dateCommissionRepository->find($idDate);

        if (null === $dateCommission) {
            return $this->json([]);
        }

        $datesLiees = $dateCommissionRepository->findDatesLiees($dateCommission);

        $dtos = array_map([DateCommissionLieeDto::class, 'fromEntity'], $datesLiees);

        $items = array_map(
            function (DateCommissionLieeDto $dto): array {
                return $dto->toArray();
            },
            $dtos
        );

        return $this->json($items);
    }
}
