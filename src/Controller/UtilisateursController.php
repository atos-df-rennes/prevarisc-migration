<?php

namespace App\Controller;

use App\Repository\GroupeRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin", name="admin_")
 */
class UtilisateursController extends AbstractController
{
    /**
     * @Route("/utilisateurs", name="utilisateurs")
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
}
