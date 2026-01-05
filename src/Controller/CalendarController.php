<?php

namespace App\Controller;

use App\Repository\DateCommissionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/calendrier-des-commissions", name="calendar_")
 */
class CalendarController extends AbstractController
{
    /** @var DateCommissionRepository */
    private $dateCommissionRepository;

    public function __construct(DateCommissionRepository $dateCommissionRepository)
    {
        $this->dateCommissionRepository = $dateCommissionRepository;
    }

    /**
     * Récupère les événements du calendrier pour une période donnée.
     *
     * @Route("/recupevenement", name="get_events", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function recupevenementAction(Request $request): JsonResponse
    {
        $startTimestamp = $request->query->get('start');
        $endTimestamp = $request->query->get('end');
        $idComm = $request->query->get('idComm');
        $type = $request->query->get('type');

        // Conversion timestamps (millisecondes) en dates
        $dateDebut = new \DateTime();
        $dateDebut->setTimestamp((int) ($startTimestamp / 1000));
        $dateDebutStr = $dateDebut->format('Y-m-d');

        $dateFin = new \DateTime();
        $dateFin->setTimestamp((int) ($endTimestamp / 1000));
        $dateFinStr = $dateFin->format('Y-m-d');

        // Récupération des événements
        $dateCommissions = $this->dateCommissionRepository->findEventsBetweenDates(
            $dateDebutStr,
            $dateFinStr,
            $idComm,
            $type
        );

        $items = [];
        foreach ($dateCommissions as $commissionEvent) {
            $items[] = [
                'id' => $commissionEvent->getId(),
                'title' => '   '.$commissionEvent->getLibelle(),
                'start' => $commissionEvent->getDate()->format('Y-m-d').' '.$commissionEvent->getHeureDebut()->format('H:i:s'),
                'end' => $commissionEvent->getDate()->format('Y-m-d').' '.$commissionEvent->getHeureFin()->format('H:i:s'),
                'url' => 'commission/id/'.$commissionEvent->getId(),
                'className' => 'display-'.$commissionEvent->getTypeEvenement()->getId(),
                'allDay' => false,
            ];
        }

        return new JsonResponse(['items' => $items]);
    }

    /**
     * Affiche le dialog pour créer/éditer une date de commission.
     *
     * @Route("/dialogcomm", name="dialog_commission", methods={"GET", "POST"})
     *
     * @return Response
     */
    public function dialogcommAction(Request $request): Response
    {
        $do = $request->query->get('do') ?? $request->request->get('do');

        // Pour l'instant, on retourne une réponse vide
        // L'implémentation complète sera faite dans l'étape 4
        return new Response('<!-- Dialog commission placeholder -->');
    }

    /**
     * Enregistre une ou plusieurs nouvelles dates de commission.
     *
     * @Route("/adddates", name="add_dates", methods={"POST"})
     *
     * @return JsonResponse
     */
    public function adddatesAction(Request $request): JsonResponse
    {
        // Pour l'instant, on retourne un succès vide
        // L'implémentation complète sera faite dans l'étape 4
        return new JsonResponse(['success' => true, 'message' => 'Dates ajoutées']);
    }
}
