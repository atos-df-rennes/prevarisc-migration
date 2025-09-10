<?php

namespace App\Controller;

use App\Entity\Etablissement;
use App\Form\TextesApplicablesEtablissementType;
use App\Repository\EtablissementRepository;
use App\Service\Etablissement as EtablissementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
     * @Route("/etablissement/{id}/textes-applicables", name="etablissement_textes_applicables")
     */
    public function textesApplicables(Etablissement $etablissement, EtablissementService $etablissementService): Response
    {
        $etablissementData = $etablissementService->getTextesApplicables($etablissement->getId());

        return $this->render('etablissement/textes_applicables.html.twig', [
            'etablissement' => $etablissement,
            'textes_applicables' => $etablissementData,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/textes-applicables/modifier", name="etablissement_textes_applicables_edit")
     */
    public function editTextesApplicables(Request $request, Etablissement $etablissement, EtablissementRepository $etablissementRepository, EntityManagerInterface $entityManager): Response
    {
        $textesApplicablesEtablissementData = $etablissementRepository->getTextesApplicablesByType($etablissement->getId());
        $form = $this->createForm(TextesApplicablesEtablissementType::class, $textesApplicablesEtablissementData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Les textes applicables ont bien été mis à jour.');

            return $this->redirectToRoute('etablissement_textes_applicables', ['id' => $etablissement->getId()]);
        }

        return $this->render('etablissement/edit_textes_applicables.html.twig', [
            'form' => $form->createView(),
            'etablissement' => $etablissement,
        ]);
    }
}
