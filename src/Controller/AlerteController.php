<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller pour la gestion des alertes email.
 *
 * Équivalent legacy : ChangementController (alerteformAction)
 */
class AlerteController extends AbstractController
{
    /**
     * Affiche le formulaire d'alerte email (modale).
     *
     * Équivalent legacy : ChangementController::alerteformAction()
     * Route legacy : /changement/alerteform
     *
     * @Route("/symfony/alerte/formulaire", name="alerte_formulaire", methods={"GET"})
     */
    public function formulaire(Request $request): Response
    {
        $etablissementId = $request->query->getInt('etablissement');
        $typeChangement = $request->query->getInt('type', 2); // 2 = avis

        if (!$etablissementId) {
            throw $this->createNotFoundException('ID établissement requis');
        }

        // @todo : Récupérer établissement et utilisateurs destinataires
        // @todo : Générer objet et message avec Service_Changement

        // Pour l'instant, retour simple pour tester la modale
        return $this->render('alerte/formulaire.html.twig', [
            'etablissementId' => $etablissementId,
            'typeChangement' => $typeChangement,
            'destinataires' => [], // @todo
            'emails' => '', // @todo
            'objet' => "Changement d'avis de commission", // @todo
            'message' => 'Message par défaut', // @todo
        ]);
    }
}
