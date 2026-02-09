<?php

namespace App\Controller;

use App\Repository\ChangementRepository;
use App\Repository\EtablissementRepository;
use App\Service\Alerte\AlerteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller pour la gestion des alertes email.
 *
 * Équivalent legacy : ChangementController (alerteformAction)
 *
 * @Route("/symfony/alerte", name="alerte_")
 */
class AlerteController extends AbstractController
{
    /**
     * Affiche le formulaire d'alerte email (modale).
     *
     * Équivalent legacy : ChangementController::alerteformAction()
     * Route legacy : /changement/alerteform
     *
     * @Route("/formulaire", name="formulaire", methods={"GET"})
     */
    public function formulaire(
        Request $request,
        EtablissementRepository $etablissementRepository,
        ChangementRepository $changementRepository,
        AlerteService $alerteService
    ): Response {
        $etablissementId = $request->query->getInt('etablissement');
        $typeChangement = $request->query->getInt('type', 2); // 2 = avis

        if (!$etablissementId) {
            throw $this->createNotFoundException('ID établissement requis');
        }

        // Récupérer l'établissement
        $etablissement = $etablissementRepository->find($etablissementId);
        if (!$etablissement) {
            throw $this->createNotFoundException(sprintf('Établissement #%d introuvable', $etablissementId));
        }

        // Récupérer le template de changement
        $changement = $changementRepository->find($typeChangement);
        if (!$changement) {
            throw $this->createNotFoundException(sprintf('Type changement #%d introuvable', $typeChangement));
        }

        // Récupérer les destinataires
        $destinataires = $alerteService->getDestinataires($typeChangement, $etablissement);

        // Formater pour le template
        $emails = [];
        foreach ($destinataires as $destinataire) {
            $emails[] = $destinataire['mail'];
        }

        // Générer objet email
        $objet = $alerteService->getObjet($typeChangement, $etablissement);

        // Générer message email avec remplacement balises
        $messageTemplate = $changement->getMessage() ?? '';
        $message = $alerteService->getMessage($messageTemplate, $etablissement);

        return $this->render('alerte/formulaire.html.twig', [
            'etablissementId' => $etablissementId,
            'typeChangement' => $typeChangement,
            'destinataires' => $destinataires,
            'emails' => implode(';', $emails),
            'objet' => $objet,
            'message' => $message,
        ]);
    }
}
