<?php

namespace App\Controller;

use App\Repository\GroupeRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function utilisateurs(UtilisateurRepository $utilisateurRepository, GroupeRepository $groupeRepository): Response
    {
        return $this->render('utilisateurs/index.html.twig', [
            'utilisateurs_actifs' => $utilisateurRepository->findActives(),
            'utilisateurs_inactifs' => $utilisateurRepository->findInactives(),
            'groupes' => $groupeRepository->findAll(),
            'groupe_id' => null,
        ]);
    }
}
