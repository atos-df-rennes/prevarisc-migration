<?php

namespace App\Controller;

use App\Entity\Etablissement;
use App\Service\Etablissement as EtablissementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EtablissementController extends AbstractController
{
    /**
     * @Route("/etablissement/{id}/historique", name="etablissement_historique")
     */
    public function historique(Etablissement $etablissement, EtablissementService $etablissementService): Response
    {
        $historique = $etablissementService->getHistorique($etablissement->getId());

        return $this->render('etablissement/historique.html.twig', [
            'etablissement' => $etablissement,
            'historique' => $historique,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/avis-derogations", name="etablissement_avisDerogations")
     */
    public function avisDerogations(Etablissement $etablissement, EtablissementService $etablissementService): Response
    {
        $historiqueAvisDerogations = $etablissementService->getAvisDerogations($etablissement->getId());
        $derogationsOriginales = $etablissement->getDescriptifDerogation();

        return $this->render('etablissement/avisDerogations.html.twig', [
            'etablissement' => $etablissement,
            'historiqueAvisDerogations' => $historiqueAvisDerogations,
            'derogationsOriginales' => $derogationsOriginales,
        ]);
    }
}
