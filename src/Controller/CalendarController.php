<?php

namespace App\Controller;

use App\Repository\DateCommissionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route("/calendrier-des-commissions/recupevenement", name="calendar_get_events", methods={"GET"})
     */
    public function recupevenement(Request $request): JsonResponse
    {
        /** @var string|null $startTimestamp */
        $startTimestamp = $request->query->get('start');
        /** @var string|null $endTimestamp */
        $endTimestamp = $request->query->get('end');
        /** @var string|null $idComm */
        $idComm = $request->query->get('idComm');
        /** @var string|null $type */
        $type = $request->query->get('type');

        if (null === $startTimestamp || null === $endTimestamp) {
            return new JsonResponse(['items' => []]);
        }

        // Conversion timestamps (millisecondes) en dates
        $dateDebut = new \DateTime();
        $dateDebut->setTimestamp((int) ((int) $startTimestamp / 1000));

        $dateDebutStr = $dateDebut->format('Y-m-d');

        $dateFin = new \DateTime();
        $dateFin->setTimestamp((int) ((int) $endTimestamp / 1000));

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
            $date = $commissionEvent->getDate();
            $heureDebut = $commissionEvent->getHeureDebut();
            $heureFin = $commissionEvent->getHeureFin();
            $typeEvenement = $commissionEvent->getTypeEvenement();
            // Skip si données obligatoires manquantes
            if (null === $date) {
                continue;
            }

            if (null === $heureDebut) {
                continue;
            }

            if (null === $heureFin) {
                continue;
            }

            if (null === $typeEvenement) {
                continue;
            }

            $items[] = [
                'id' => $commissionEvent->getId(),
                'title' => '   '.$commissionEvent->getLibelle(),
                'start' => $date->format('Y-m-d').' '.$heureDebut->format('H:i:s'),
                'end' => $date->format('Y-m-d').' '.$heureFin->format('H:i:s'),
                'url' => 'commission/id/'.$commissionEvent->getId(),
                'className' => 'display-'.$typeEvenement->getId(),
                'allDay' => false,
            ];
        }

        return new JsonResponse(['items' => $items]);
    }

    /**
     * Affiche le dialog pour créer/éditer une date de commission.
     *
     * @Route("/calendrier-des-commissions/dialogcomm", name="calendar_dialog_commission", methods={"GET", "POST"})
     */
    public function dialogcomm(Request $request): Response
    {
        $request->query->get('do') ?? $request->request->get('do');

        // Pour l'instant, on retourne une réponse vide
        // L'implémentation complète sera faite dans l'étape 4
        return new Response('<!-- Dialog commission placeholder -->');
    }

    /**
     * Enregistre une ou plusieurs nouvelles dates de commission.
     *
     * @Route("/calendrier-des-commissions/adddates", name="calendar_add_dates", methods={"POST"})
     */
    public function adddates(): JsonResponse
    {
        // Pour l'instant, on retourne un succès vide
        // L'implémentation complète sera faite dans l'étape 4
        return new JsonResponse(['success' => true, 'message' => 'Dates ajoutées']);
    }

    /**
     * Récupère les dates liées à une date de commission.
     *
     * @Route("/calendrier-des-commissions/recupdateliee", name="calendar_get_linked_dates", methods={"GET"})
     */
    public function recupdateliee(Request $request): JsonResponse
    {
        /** @var string|null $idDate */
        $idDate = $request->query->get('idDate');

        if (null === $idDate) {
            return new JsonResponse([]);
        }

        // Pour l'instant, retourner la date elle-même (pas de dates liées)
        // L'implémentation complète sera faite quand nécessaire
        $dateCommission = $this->dateCommissionRepository->find((int) $idDate);

        if (null === $dateCommission) {
            return new JsonResponse([]);
        }

        $date = $dateCommission->getDate();
        $heureDebut = $dateCommission->getHeureDebut();
        $heureFin = $dateCommission->getHeureFin();

        if (in_array(null, [$date, $heureDebut, $heureFin], true)) {
            return new JsonResponse([]);
        }

        return new JsonResponse([
            [
                'id' => $dateCommission->getId(),
                'start' => $date->format('Y-m-d').' '.$heureDebut->format('H:i:s'),
                'end' => $date->format('Y-m-d').' '.$heureFin->format('H:i:s'),
            ],
        ]);
    }

    /**
     * Déplace une date de commission (drag & drop).
     *
     * @Route("/calendrier-des-commissions/deplacecommissiondate", name="calendar_move_commission_date", methods={"POST"})
     */
    public function deplacecommissiondate(): JsonResponse
    {
        // Pour l'instant, on retourne un succès
        // L'implémentation complète sera faite quand nécessaire
        return new JsonResponse(['success' => true]);
    }

    /**
     * Redimensionne une date de commission (resize).
     *
     * @Route("/calendrier-des-commissions/resizecommissiondate", name="calendar_resize_commission_date", methods={"POST"})
     */
    public function resizecommissiondate(): JsonResponse
    {
        // Pour l'instant, on retourne un succès
        // L'implémentation complète sera faite quand nécessaire
        return new JsonResponse(['success' => true]);
    }
}
