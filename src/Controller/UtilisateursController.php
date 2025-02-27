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
 * @Route("/admin/utilisateurs", name="admin_utilisateurs_")
 */
class UtilisateursController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function utilisateurs(Request $request, UtilisateurRepository $utilisateurRepository, GroupeRepository $groupeRepository): Response
    {
        $groupeId = $request->query->get('gid');

        return $this->render('utilisateurs/index.html.twig', [
            'utilisateurs_actifs' => $utilisateurRepository->findActives($groupeId),
            'utilisateurs_inactifs' => $utilisateurRepository->findInactives($groupeId),
            'groupes' => $groupeRepository->findAll(),
            'groupe_id' => $groupeId,
        ]);
    }

    /**
     * @Route("/ajout-groupe", name="add_group")
     */
    public function ajoutGroupe(Request $request): Response
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

        return $this->render('utilisateurs/ajout_groupe.html.twig', [
            'form' => $form->createView(),
            'errors' => $form->getErrors(),
        ]);
    }
}
