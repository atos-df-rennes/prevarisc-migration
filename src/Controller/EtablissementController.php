<?php

namespace App\Controller;

use App\Entity\Etablissement;
use App\Form\TextesApplicablesEtablissementType;
use App\Repository\EtablissementRepository;
use App\Repository\TexteApplicableRepository;
use App\Service\Etablissement as EtablissementService;
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
    public function editTextesApplicables(Request $request, Etablissement $etablissement, EtablissementRepository $etablissementRepository, TexteApplicableRepository $texteApplicableRepository): Response
    {
        $textesApplicablesEtablissementData = $etablissementRepository->getTextesApplicablesByType($etablissement->getId());

        $form = $this->createForm(TextesApplicablesEtablissementType::class, $textesApplicablesEtablissementData);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Etablissement $etablissement */
            $etablissement = $form->getData();
            $textesApplicablesAjoutes = $etablissement->getTextesApplicables();
            foreach ($textesApplicablesAjoutes as $ta) {
                $etablissement->addTextesApplicable($ta);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($etablissement);
            $entityManager->flush();

            $this->addFlash('success', 'Les textes applicables ont bien été mis à jour.');

            return $this->redirectToRoute('etablissement_textesApplicables', ['id' => $etablissement->getId()]);
        }

        return $this->render('etablissement/edit-textes-applicables.html.twig', [
            'form' => $form->createView(),
            'etablissement' => $etablissement,
        ]);
    }
}
