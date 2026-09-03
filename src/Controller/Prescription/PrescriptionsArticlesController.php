<?php

namespace App\Controller\Prescription;

use App\Entity\PrescriptionArticleListe;
use App\Form\PrescriptionArticleListeType;
use App\Form\ReplacePrescriptionArticleListeType;
use App\Repository\PrescriptionArticleListeRepository;
use App\Service\Prescriptions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('gestion_prescriptions')]
class PrescriptionsArticlesController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly Prescriptions $servicePresc, private readonly PrescriptionArticleListeRepository $prescriptionArticleListeRepository)
    {
    }

    #[Route(path: '/admin/prescriptions/articles/', name: 'admin_prescriptions_articles')]
    public function gestionArticles(): Response
    {
        $listeArticles = $this->servicePresc->getArticlesListe();

        return $this->render('prescriptions_articles/articles.html.twig', [
            'liste_articles' => $listeArticles,
        ]);
    }

    #[Route(path: '/admin/prescriptions/articles/ajouter', name: 'admin_prescriptions_articles_add')]
    #[Route(path: '/admin/prescriptions/articles/modifier/{articleListe}', name: 'admin_prescriptions_articles_edit')]
    public function formArticle(Request $request, ?PrescriptionArticleListe $articleListe = null): Response
    {
        $articleListe ??= new PrescriptionArticleListe();

        $form = $this->createForm(PrescriptionArticleListeType::class, $articleListe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->persist($articleListe);
                $this->entityManager->flush();

                $this->addFlash('success', 'L\'article a bien été enregistré');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Une erreur s\'est produite lors de l\enregistrement de la prescription ('.$e->getMessage().')');
            }

            return $this->redirectToRoute('admin_prescriptions_articles');
        }

        return $this->render('prescriptions_articles/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/admin/prescriptions/articles/suppression/{articleListe}', name: 'admin_prescriptions_articles_suppression')]
    public function suppressionArticle(
        Request $request,
        PrescriptionArticleListe $articleListe
    ): Response {
        $form = $this->createForm(ReplacePrescriptionArticleListeType::class, null, ['excluded' => $articleListe]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newArticle = $form->get('replacementArticle')->getData();

            if (!$newArticle instanceof PrescriptionArticleListe) {
                throw new \Exception("L'article de remplacement n'est pas correcte.");
            }

            try {
                $this->prescriptionArticleListeRepository->replaceAndRemove($articleListe, $newArticle);
                $this->addFlash('success', 'L\'article a bien été supprimé');
            } catch (\Exception $e) {
                $this->addFlash('danger', "Une erreur s'est produite lors de la suppression du article (".$e->getMessage().')');
            }

            return $this->redirectToRoute('admin_prescriptions_articles');
        }

        return $this->render('prescriptions_articles/form_replacement.html.twig', [
            'form' => $form,
        ]);
    }
}
