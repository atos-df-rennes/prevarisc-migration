<?php

namespace App\Controller;

use App\Entity\Etablissement;
use App\Repository\UtilisateurInformationsRepository;
use App\Repository\UtilisateurRepository;
use App\Service\Etablissement as EtablissementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EtablissementController extends AbstractController
{
    /**
     * @Route("/etablissement/{id}/historique", name="etablissement_historique")
     */
    public function historique(Etablissement $etablissement, EtablissementService $etablissementService, UtilisateurRepository $utilisateurRepository, UtilisateurInformationsRepository $utilisateurInformationsRepository): Response
    {
        if ($etablissement != null) {
            $historique = $etablissementService->getHistorique($etablissement->getId());
        }
        else {
            $historique = [];
        }

        return $this->render('etablissement/historique.html.twig', [
            'etablissement' => $etablissement,
            'historique' => $historique,
        ]);
    }
}
