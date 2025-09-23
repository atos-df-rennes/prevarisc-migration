<?php

namespace App\Controller;

use App\Entity\PrescriptionArticleListe;
use App\Form\PrescriptionArticleListeType;
use App\Form\ReplacePrescriptionArticleListeType;
use App\Repository\PrescriptionArticleListeRepository;
use App\Service\Prescriptions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/prescriptions/articles")
 */
class PrescriptionsArticlesController extends AbstractController
{
    /**
     * @Route("/", name="admin_prescriptions_articles")
     */
    public function gestionArticles(Prescriptions $servicePresc): Response
    {
        $listeArticles = $servicePresc->getArticlesListe();

        return $this->render('prescriptions_articles/articles.html.twig', [
            'liste_articles' => $listeArticles,
        ]);
    }

    /**
     * @Route("/ajouter", name="admin_prescriptions_articles_add")
     * @Route("/modifier/{articleListe}", name="admin_prescriptions_articles_edit")
     */
    public function formArticle(Request $request, ?PrescriptionArticleListe $articleListe = null): Response
    {
        if (null === $articleListe) {
            $articleListe = new PrescriptionArticleListe();
        }

        $form = $this->createForm(PrescriptionArticleListeType::class, $articleListe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($articleListe);
                $em->flush();

                $this->addFlash('success', 'L\'article a bien été enregistré');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Une erreur s\'est produite lors de l\enregistrement de la prescription ('.$e->getMessage().')');
            }

            return $this->redirectToRoute('admin_prescriptions_articles');
        }

        return $this->render('prescriptions_articles/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/suppression/{articleListe}", name="admin_prescriptions_articles_suppression")
     */
    public function suppressionArticle(
        Request $request,
        PrescriptionArticleListe $articleListe,
        PrescriptionArticleListeRepository $prescriptionArticleListeRepository
    ): Response {
        $form = $this->createForm(ReplacePrescriptionArticleListeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newArticle = $form->get('replacementArticle')->getData();

            if (!$newArticle instanceof PrescriptionArticleListe) {
                throw new \Exception("L'article de remplacement n'est pas correcte.");
            }

            try {
                $prescriptionArticleListeRepository->replaceAndRemove($articleListe, $newArticle);
                $this->addFlash('success', 'L\'article a bien été supprimé');
            } catch (\Exception $e) {
                $this->addFlash('danger', "Une erreur s'est produite lors de la suppression du article (".$e->getMessage().')');
            }

            return $this->redirectToRoute('admin_prescriptions_articles');
        }

        return $this->render('prescriptions_articles/form_replacement.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
