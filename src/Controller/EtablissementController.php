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
     * @Route("/etablissement/{id}/textes-applicables", name="etablissement_textesApplicables")
     */
    public function textesApplicables(Etablissement $etablissement, EtablissementService $etablissementService): Response
    {
        $etablissementData = $etablissementService->getTextesApplicablesEtablissement($etablissement->getId());

        return $this->render('etablissement/textes-applicables.html.twig', [
            'etablissement' => $etablissement,
            'textes_applicables' => $etablissementData,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/textes-applicables/edit", name="etablissement_textesApplicables_edit")
     */
    public function editTextesApplicables(Etablissement $etablissement, EtablissementService $etablissementService): Response
    {
        return $this->render('etablissement/edit-textes-applicables.html.twig', []);
    }

    /**
     * @Route("/etablissement/{id]/textes-applicables/add", name="etablissement_textesApplicables_add")
     */
    public function addTexteApplicable(Etablissement $etablissement, EtablissementService $etablissementService): Response
    {
        return $this->render('etablissement/add-textes-applicables.html.twig', []);
    }
}
