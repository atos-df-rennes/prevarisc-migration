<?php

namespace App\Controller;

use App\Service\Prescriptions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PrescriptionsController extends AbstractController {

    /**
     * @Route("/admin/prescriptions/textes", name="admin_prescriptions_textes")
     */
    public function gestionTextesAction(Request $request, Prescriptions $servicePrescTextes): Response {
        if ($request->isMethod('POST')) {
            try {
                $post = $request->request->all();

                if ('add' === $post['action']) {
                    $servicePrescTextes->saveTexte($post);
                    $this->addFlash('success', 'La texte a bien été enregistré');
                } elseif ('edit' === $post['action']) {
                    $servicePrescTextes->saveTexte($post, $post['id_texte']);
                    $this->addFlash('success', 'La texte a bien été enregistré');
                } elseif ('replace' === $post['action']) {
                    $servicePrescTextes->replaceTexte($post['id_texte'], $post['idTexteReplace']);
                    $this->addFlash('success', 'Le texte a bien été supprimé');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur s\'est produite lors de l\'enregistrement de la prescription (' . $e->getMessage() . ')');
            }
        }

        $listeTextes = $servicePrescTextes->getTextesListe();

        return $this->render('prescriptions/textes.html.twig', [
            'liste_textes' => $listeTextes,
        ]);
    }

}
