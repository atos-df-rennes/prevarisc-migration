<?php

namespace App\Controller;

use App\Dto\Commission\CalendarEventDto;
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
}
