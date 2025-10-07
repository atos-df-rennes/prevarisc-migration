<?php

namespace App\Controller;

use App\Entity\Rubrique;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("gestion_formulaires")
 */
class RubriqueController extends AbstractController
{
    /**
     * @Route("/admin/formulaires/rubriques/{id}/supprimer", name="admin_formulaire_delete_rubrique")
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
