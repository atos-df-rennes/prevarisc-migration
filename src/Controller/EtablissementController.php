<?php

namespace App\Controller;

use App\Entity\Etablissement;
use App\Repository\EtablissementRepository;
use App\Repository\UtilisateurInformationsRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EtablissementController extends AbstractController
{
    /**
     * @Route("/etablissement/{id}", name="etablissement_index")
     */
    public function index(Etablissement $etablissement): Response
    {
        $etablissementId = $etablissement->getId();
        return $this->render('etablissement/index.html.twig', [
            'etablissement' => $etablissement,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/historique", name="etablissement_historique")
     */
    public function historique(Etablissement $etablissement, \App\Service\Etablissement $etablissementService, UtilisateurRepository $utilisateurRepository, UtilisateurInformationsRepository $utilisateurInformationsRepository): Response
    {
        $historique = [];
//        $historique = $etablissementService->getHistorique($etablissement->getId());

        return $this->render('etablissement/historique.html.twig', [
            'etablissement' => $etablissement,
            'historique' => $historique,
        ]);
    }
}
