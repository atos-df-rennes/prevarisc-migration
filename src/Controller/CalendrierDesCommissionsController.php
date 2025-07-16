<?php

namespace App\Controller;

use App\Entity\Dossier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CommissionTypeEvenementRepository;
use App\Entity\DateCommission;
use App\Repository\DateCommissionRepository;
use App\Repository\CommissionRepository;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;



class CalendrierDesCommissionsController extends AbstractController{

     /**
     * @Route("/evenements", name="calendrier_evenements", methods={"GET"})
     */
    public function recupEvenement(Request $request, DateCommissionRepository $dateCommissionRepository): JsonResponse
    {
        $start = new \DateTime($request->query->get('start'));
        $end = new \DateTime($request->query->get('end'));
        $idComm = $request->query->get('idComm');
        $type = $request->query->get('type');

        $events = $dateCommissionRepository->findEventsBetween($start, $end, $idComm, $type);

        $items = [];

        foreach ($events as $event) {
            // Création des datetime de début et de fin
            $startDateTime = (clone $event->getDateCommission())->setTime(
                (int) $event->getHeureDebutCommission()->format('H'),
                (int) $event->getHeureDebutCommission()->format('i')
            );

            $endDateTime = (clone $event->getDateCommission())->setTime(
                (int) $event->getHeureFinCommission()->format('H'),
                (int) $event->getHeureFinCommission()->format('i')
            );

            // Sécurité sur le type d'événement (null possible)
            $commissionType = $event->getCommissionTypeEvenement();
            $className = $commissionType ? 'display-' . $commissionType->getId() : 'display-default';

            // Construction de l'élément
            $items[] = [
                'id' => $event->getId(),
                'title' => '   ' . $event->getLibelleDateCommission(),
                'start' => $startDateTime->format('Y-m-d H:i:s'),
                'end' => $endDateTime->format('Y-m-d H:i:s'),
                'url' => 'commission/id/' . $event->getId(),
                'allDay' => false,
                'className' => $className,
            ];
        }

        return new JsonResponse($items);
    }

    /**
     * @Route("/dialogcomm", name="dialogcomm", methods={"GET", "POST"})
     */
    public function dialogcomm(Request $request, CommissionTypeEvenementRepository $commTypeEventRepo): Response
    {
        $do = $request->request->get('do');

        switch ($do) { 
            case 'newComm': return $this->handleNewComm($request, $commTypeEventRepo);
            case 'addDateN': return $this->handleAddDateN($request);
            default: return new JsonResponse(['error' => 'Action non prise en charge'], 400);
        }
    }

    /**
     * @Route("/recupdateliee", name="recupdateliee", methods={"GET"})
     */
    public function recupdateliee( Request $request, DateCommissionRepository $dateCommissionRepo, EntityManagerInterface $em): JsonResponse {
        $idDate = $request->query->get('idDate');

        if (!$idDate) {
            return new JsonResponse(['error' => 'Paramètre idDate manquant'], 400);
        }

        $infosDateComm = $dateCommissionRepo->find($idDate);

        if (!$infosDateComm) {
            return new JsonResponse(['error' => 'Date non trouvée'], 404);
        }

        // Récupération de la commission principale
        $commPrincipale = $infosDateComm->getDateCommissionLiees();
        if (empty($commPrincipale)) {
            $commPrincipale = $idDate;
        }

        // Appel à une méthode personnalisée dans le repository
        $recupCommLiees = $dateCommissionRepo->getCommissionsDateLieesMaster($commPrincipale);

        return new JsonResponse($recupCommLiees);
    }

    /**
     * @Route("/deplaceCommissionDate", name="deplaceCommissionDate", methods={"POST"})
     */
    public function deplaceCommissionDate( Request $request, DateCommissionRepository $dateCommissionRepository, EntityManagerInterface $em): JsonResponse {
        try {
            $idComm = $request->request->get('idComm');
            $debut = $request->request->get('debut');
            $fin = $request->request->get('fin');

            if (!$idComm || !$debut ||  !$fin) {
                return new JsonResponse(['error' => 'Paramètres manquants'], 400);
            }

            $commUpdate = $dateCommissionRepository->find($idComm);

            if (!$commUpdate) {
                return new JsonResponse(['error' => 'DateCommission non trouvée'], 404);
            }

            // Conversion des dates
            $dateDebut = new \DateTime($debut);
            $commUpdate->setDateCommission(clone $dateDebut);
            $commUpdate->setHeureDebutCommission($dateDebut);

            if ($fin) {
                $dateFin = new \DateTime($fin);
                $commUpdate->setHeureFinCommission($dateFin);
            }

            $em->flush();

            $dateCommissionRepository->updateDependingDossierDates($commUpdate);

            return new JsonResponse(['success' => 'Événement déplacé avec succès']);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error'=>'Erreur mouvement',
                'message'=> $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ],500);
        }
    }

    /**
     * @Route("/resizeCommissionDate", name="resizeCommissionDate", methods={"POST"})
     */
    public function resizeCommissionDate(Request $request, EntityManagerInterface $em, DateCommissionRepository $dateCommissionRepository): JsonResponse
    {
        try {
            $idComm = $request->request->get('idComm');
            $fin = $request->request->get('fin'); 
            if (!$idComm || !$fin) {
                return new JsonResponse(['error' => 'Paramètres manquants'], 400);
            }

            $commResize = $dateCommissionRepository->find($idComm);
            if (!$commResize) { 
                return new JsonResponse([ 'error' => 'Commission non trouvée'], 404);
            }

            $dateFin = new \DateTime($fin, new \DateTimeZone('Europe/Paris'));
            $commResize->setHeureFinCommission($dateFin);
            $em->flush();

            return new JsonResponse(['success' => 'Heure de fin modifiée avec succès.']);

        } catch (\Exception $exception) {
            return new JsonResponse([
                'error' => 'Erreur lors de la modification de l\'événement',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * @Route("/adddates", name="add_dates", methods={"POST"})
     */

    public function addDates(Request $request, EntityManagerInterface $em, CommissionTypeEvenementRepository $typeRepo, CommissionRepository $commissionRepo): JsonResponse
    {
        try {
            $libelle = $request->get('libelle_comm');
            $typeCom = $request->get('typeCom');
            $idComm = $request->get('idComm');
            $repeat = $request->get('repeat');
            $commission = $commissionRepo->find($idComm);
            if (!$commission) {
                return new JsonResponse(['error' => 'Commission introuvable'], 400);
            }
            $listeDates = [];

        if (strtolower($repeat) === "on") {
            $periodicite = (int) $request->get('periodicite');
            $dateFin = \DateTime::createFromFormat('d.m.Y', $request->get('dateFin'));

            foreach ($request->request->all() as $key => $value) {
                if (preg_match('/^D_(\d{4}-\d{2}-\d{2})$/', $key, $matches)) {
                    $dateStr = $matches[1];
                    $date = new \DateTime($dateStr);
                    $heureDebut = \DateTime::createFromFormat('H:i', $value);
                    $heureFin = \DateTime::createFromFormat('H:i', $request->get("F_$dateStr"));

                    while ($date <= $dateFin) {
                        $dateCommission = new DateCommission();
                        $dateCommission->setDateCommission(clone $date)
                            ->setHeureDebutCommission($heureDebut)
                            ->setHeureFinCommission($heureFin)
                            ->setLibelleDateCommission($libelle)
                            ->setCommissionTypeEvenement($typeRepo->find($typeCom))
                            ->setCommissionConcerne($commission)
                            ->setGestionHeures(true);

                        $em->persist($dateCommission);
                        $em->flush();

                        $listeDates[] = [
                            'id' => $dateCommission->getId(),
                            'title' => $libelle,
                            'start' => $date->format('Y-m-d') . ' ' . $heureDebut->format('H:i'),
                            'end' => $date->format('Y-m-d') . ' ' . $heureFin->format('H:i'),
                            'url' => 'calendrier-des-commissions/id/' . $dateCommission->getId(),
                            'className' => 'display-' . $typeCom,
                        ];

                        $date->modify('+'.(7 * $periodicite).' days');
                    }
                }
            }
        } else {
            // Plusieurs dates manuelles
            foreach ($request->request->all() as $key => $value) {
                if (preg_match('/^D_(\d{4}-\d{2}-\d{2})$/', $key, $matches)) {
                    $dateStr = $matches[1];
                    $date = new \DateTime($dateStr);
                    $heureDebut = \DateTime::createFromFormat('H:i', $value);
                    $heureFin = \DateTime::createFromFormat('H:i', $request->get("F_$dateStr"));

                    $dateCommission = new DateCommission();
                    $dateCommission->setDateCommission($date)
                        ->setHeureDebutCommission($heureDebut)
                        ->setHeureFinCommission($heureFin)
                        ->setLibelleDateCommission($libelle)
                        ->setCommissionConcerne($commission)
                        ->setCommissionTypeEvenement($typeRepo->find($typeCom))
                        ->setGestionHeures(true);

                    $em->persist($dateCommission);
                    $em->flush();

                    $listeDates[] = [
                        'id' => $dateCommission->getId(),
                        'title' => $libelle,
                        'start' => $date->format('Y-m-d') . ' ' . $heureDebut->format('H:i'),
                        'end' => $date->format('Y-m-d') . ' ' . $heureFin->format('H:i'),
                        'url' => 'calendrier-des-commissions/id/' . $dateCommission->getId(),
                        'className' => 'display-' . $typeCom,
                    ];
                }
            }
        }
            return new JsonResponse([
                'success' => true,
                'dates' => $listeDates,
                'datesLength' => count($listeDates)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage(),
            ], 500);
        }
    }

    private function handleNewComm(Request $request, CommissionTypeEvenementRepository $repo): Response
    {
        $libelleCom = $request->request->get('libelleCom') ?? '';

        $listeCommType = $repo->findAll(); 

        $dateD = new \DateTime($request->request->get('dateD'));
        $dateF = new \DateTime($request->request->get('dateF'));
        $dates = [];
        $it = clone $dateD;
        while ($it <= $dateF) {
            $it->modify('+1 day');
            $dates[] = [
                'date' => $it->format('l d M Y'),
                'inputH' => $it->format('Y-m-d'),
                'heureD' => $dateD->format('H:i'),
                'heureF' => $dateF->format('H:i'),
            ];
        }
                $dateD = clone $it;

        return $this->render('calendrier/dialogcomm_new.html.twig', [
            'do' => 'newComm',
            'libelleCom' => $libelleCom,
            'listeCommType' => $listeCommType,
            'dateCommD' => $dateD->format('l d M Y'),
            'dateSelectD' => $dateD->format('Y/m/d'),
            'dateCommF' => $dateF->format('l d M Y'),
            'dateSelectF' => $dateF->format('Y/m/d'),
            'typeDoss' => $request->request->get('type', ''),
            'listeDates' => $dates,
        ]);
    }

    private function handleAddDateN(Request $request): Response
    {   
        $date = \DateTime::createFromFormat('d/m/Y', $request->request->get('date'));
        return $this->render('calendrier/partials/_add_date_n.html.twig', [
            'dateAjoutee' => $date->format('l d M Y'),
            'dateAjouteeInput' => $date->format('Y-m-d'),
        ]);
    }

}