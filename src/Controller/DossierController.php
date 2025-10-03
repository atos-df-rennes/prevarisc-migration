<?php

namespace App\Controller;

use App\Entity\Dossier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DossierController extends AbstractController
{
    // @todo: Ajouter la vérification du privilège verrouillage de dossier
    /**
     * @Route("/dossier/{id}/verrouiller", name="dossier_verrouiller")
     */
    public function verrouiller(Request $request, Dossier $dossier, EntityManagerInterface $entityManager): Response
    {
        $dossier->setVerrou(true);
        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();
        $dossier->setUtilisateurVerrou($user->getId());

        $entityManager->flush();

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/dossier/{id}/deverrouiller", name="dossier_deverrouiller")
     */
    public function deverrouiller(Request $request, Dossier $dossier, EntityManagerInterface $entityManager): Response
    {
        $dossier->setVerrou(false);
        $dossier->setUtilisateurVerrou(null);

        $entityManager->flush();

        return $this->redirect($request->headers->get('referer'));
    }
}
