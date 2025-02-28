<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\UtilisateurPreferences;
use App\Form\UtilisateurType;
use App\Repository\GroupeRepository;
use App\Repository\UtilisateurRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/utilisateurs", name="admin_utilisateurs_")
 * @IsGranted("admin")
 */
class UtilisateursController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function utilisateurs(UtilisateurRepository $utilisateurRepository, GroupeRepository $groupeRepository): Response
    {
        return $this->render('utilisateurs/index.html.twig', [
            'utilisateurs_actifs' => $utilisateurRepository->findActives(),
            'utilisateurs_inactifs' => $utilisateurRepository->findInactives(),
            'groupes' => $groupeRepository->findAll(),
            'groupe_id' => null,
        ]);
    }

    /**
     * @Route("/ajouter", name="add")
     */
    public function ajouter(Request $request): Response
    {
        $utilisateur = new Utilisateur();

        $form = $this->createForm(UtilisateurType::class, $utilisateur);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $preferences = new UtilisateurPreferences();
            $preferences->setUtilisateur($utilisateur);
            $utilisateur->setPreferences($preferences);
            // @fixme: Utiliser l'encodage de Symfony + ré-encoder les mots de passe existants
            $utilisateur->setPassword(md5($utilisateur->getUsername().$this->getParameter('app.security_salt').$utilisateur->getPlainPassword()));

            // @todo: Gérer l'upload de l'avatar

            $entityManager->persist($preferences);
            $entityManager->persist($utilisateur);
            $entityManager->flush();

            return $this->redirectToRoute('admin_utilisateurs_index');
        }

        return $this->render('utilisateurs/ajout.html.twig', [
            'form' => $form->createView(),
            'errors' => $form->getErrors(),
        ]);
    }
}
