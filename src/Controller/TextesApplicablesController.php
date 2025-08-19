<?php

namespace App\Controller;

use App\Entity\PrestationTexteListe;
use App\Entity\TexteApplicable;
use App\Form\TexteApplicableType;
use App\Repository\TexteApplicableRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TextesApplicablesController extends AbstractController
{
    /**
     * @Route("/admin/textes-applicables", name="admin_textes_applicables")
     */
    public function index(TexteApplicableRepository $texteApplicableRepository): Response
    {
        $textesApplicables = $texteApplicableRepository->getAllSorted();

        return $this->render('textes_applicables/index.html.twig', [
            'textesApplicables' => $textesApplicables,
        ]);
    }

    /**
     * @Route("/admin/textes-applicables/add", name="admin_textes_applicables_add")
     */
    public function ajouter(Request $request): Response
    {
        $texteApplicable = new TexteApplicable();

        return $this->formRender($texteApplicable, $request);
    }

    /**
     * @Route("/admin/textes-applicables/{id}/edit", name="admin_textes_applicables_edit")
     */
    public function modifier(Request $request, TexteApplicableRepository $texteApplicableRepository): Response
    {
        $texteApplicable = $texteApplicableRepository->find($request->get('id'));

        return $this->formRender($texteApplicable, $request);
    }

    private function formRender(?TexteApplicable $texteApplicable, Request $request): Response
    {
        if ($texteApplicable != null) {
            if (null !== $texteApplicable->getId()) {
                $url = $this->generateUrl('admin_textes_applicables_edit', ['id' => $texteApplicable->getId()]);
            } else {
                $url = $this->generateUrl('admin_textes_applicables_add');
            }

            $form = $this->createForm(TexteApplicableType::class, $texteApplicable, [
                'action' => $url,
            ]);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager = $this->getDoctrine()->getManager();
                // TODO gestion de l'affichage prescription
                // la valeur oui/non n'est pas conservé, peut etre le gerer mais quid des consequences d'affichage/masquaque
                $prescription = $form->get('prescription')->getData();
                if ($prescription) {
                    $prescriptionListe = new PrestationTexteListe();
                    $prescriptionListe->setLibelle($texteApplicable->getLibelle());
                    $entityManager->persist($prescriptionListe);
                }

                $entityManager->persist($texteApplicable);
                $entityManager->flush();

                $this->addFlash('success', 'Texte applicable enregistré');

                return $this->redirectToRoute('admin_textes_applicables');
            }

            return $this->render('textes_applicables/ajout.html.twig', [
                'form' => $form->createView(),
            ]);
        }
        else {
            $this->addFlash('error', "Ce texte n'existe pas");
            return $this->redirectToRoute('admin_textes_applicables');
        }
    }

    /**
     * @Route("/admin/textes-applicables/updateorder", name="admin_textes_applicables_updateorder")
     */
    public function ordonner(Request $request, TexteApplicableRepository $texteApplicableRepository): Response
    {
        $taIds = explode(',', $request->get('tableUpdate'));
        $entityManager = $this->getDoctrine()->getManager();
        foreach ($taIds as $key => $id) {
            $texteApplicable = $texteApplicableRepository->find($id);
            if ($texteApplicable != null) {
                $texteApplicable->setNum($key);
                $entityManager->persist($texteApplicable);
            }
        }

        $entityManager->flush();

        return $this->redirectToRoute('admin_textes_applicables');
    }
}
