<?php

namespace App\Controller;

use App\Entity\Groupe;
use App\Form\GroupeType;
use App\Repository\GroupeRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/groupe", name="admin_groupe_", requirements={"id"="\d+"})
 */
class GroupeController extends AbstractController
{
    /**
     * @Route("/{id}/utilisateurs", name="index")
     */
    public function index(Groupe $groupe, GroupeRepository $groupeRepository, UtilisateurRepository $utilisateurRepository): Response
    {
        $groupeId = $groupe->getId();

        return $this->render('utilisateurs/index.html.twig', [
            'utilisateurs_actifs' => $utilisateurRepository->findActives($groupeId),
            'utilisateurs_inactifs' => $utilisateurRepository->findInactives($groupeId),
            'groupes' => $groupeRepository->findAll(),
            'groupe_id' => $groupeId,
        ]);
    }

    /**
     * @Route("/ajouter", name="add")
     */
    public function ajouter(Request $request): Response
    {
        $groupe = new Groupe();

        $form = $this->createForm(GroupeType::class, $groupe);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($groupe);
            $entityManager->flush();

            $this->addFlash('success', 'Le groupe a bien été ajouté.');

            return $this->redirectToRoute('admin_utilisateurs_index');
        }

        return $this->render('groupe/ajout.html.twig', [
            'form' => $form->createView(),
            'errors' => $form->getErrors(),
        ]);
    }

    /**
     * @Route("/{id}/modifier", name="edit")
     */
    public function modifier(Request $request, Groupe $groupe): Response
    {
        $form = $this->createForm(GroupeType::class, $groupe);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            $this->addFlash('success', 'Le groupe a bien été modifié.');

            return $this->redirectToRoute('admin_utilisateurs_index');
        }

        return $this->render('groupe/modification.html.twig', [
            'form' => $form->createView(),
            'errors' => $form->getErrors(),
            'groupe_id' => $groupe->getId(),
        ]);
    }

    // @todo: Suppression d'un groupe
}
