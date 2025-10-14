<?php

namespace App\Controller;

use App\Entity\CapsuleRubrique;
use App\Entity\Rubrique;
use App\Form\RubriqueType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("gestion_formulaires")
 */
class RubriqueController extends AbstractController
{

    /**
     * @Route("/admin/formulaires/{formulaire}/rubriques/{rubrique}/modifier", name="admin_formulaire_edit_rubrique", methods={"GET", "POST"})
     *
     * @ParamConverter("formulaire", options={"id" = "formulaire"})
     * @ParamConverter("rubrique", options={"id" = "rubrique"})
     */
    public function edit(Request $request, CapsuleRubrique $formulaire, Rubrique $rubrique, EntityManagerInterface $entityManager): Response
    {
        $formRubrique = $this->createForm(RubriqueType::class, $rubrique);

        $formRubrique->handleRequest($request);
        if ($formRubrique->isSubmitted() && $formRubrique->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La rubrique a bien été modifiée.');

            return $this->redirectToRoute('admin_formulaire_edit_rubrique', [
                'formulaire' => $formulaire->getId(),
                'rubrique' => $rubrique->getId(),
            ]);
        }

        return $this->render('formulaires/rubrique/modification.html.twig', [
            'rubrique' => $rubrique,
            'form_rubrique' => $formRubrique->createView(),
        ]);
    }

    /**
     * @Route("/admin/formulaires/rubriques/{id}/supprimer", name="admin_formulaire_delete_rubrique", methods={"DELETE"})
     */
    public function delete(Rubrique $rubrique, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($rubrique);
        $entityManager->flush();

        return $this->redirectToRoute('admin_formulaires');
    }

    /**
     * @Route("/admin/formulaires/rubriques/{id}/index/{idx}", name="admin_formulaire_index_rubrique")
     */
    public function index(Rubrique $rubrique, int $idx, EntityManagerInterface $entityManager): JsonResponse
    {
        $rubrique->setIdx($idx);
        $entityManager->flush();

        return $this->json([]);
    }
}
