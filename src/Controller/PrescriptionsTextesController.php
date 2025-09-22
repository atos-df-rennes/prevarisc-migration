<?php

namespace App\Controller;

use App\Entity\PrescriptionTexteListe;
use App\Form\PrescriptionTexteListeType;
use App\Form\ReplacePrescriptionTexteListeType;
use App\Repository\PrescriptionTexteListeRepository;
use App\Service\Prescriptions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/prescriptions/textes")
 */
class PrescriptionsTextesController extends AbstractController
{
    /**
     * @Route("/", name="admin_prescriptions_textes")
     */
    public function gestionTextes(Prescriptions $servicePrescTextes): Response
    {
        $listeTextes = $servicePrescTextes->getTextesListe();

        return $this->render('prescriptions_textes/textes.html.twig', [
            'liste_textes' => $listeTextes,
        ]);
    }

    /**
     * @Route("/ajouter", name="admin_prescriptions_textes_add")
     * @Route("/modifier/{texteListe}", name="admin_prescriptions_textes_edit")
     */
    public function formTexte(Request $request, ?PrescriptionTexteListe $texteListe = null): Response
    {
        if (null === $texteListe) {
            $texteListe = new PrescriptionTexteListe();
        }

        $form = $this->createForm(PrescriptionTexteListeType::class, $texteListe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($texteListe);
                $em->flush();

                $this->addFlash('success', 'Le texte a bien été enregistré');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Une erreur s\'est produite lors de l\enregistrement de la prescription ('.$e->getMessage().')');
            }

            return $this->redirectToRoute('admin_prescriptions_textes');
        }

        return $this->render('prescriptions_textes/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/suppression/{texteListe}", name="admin_prescriptions_textes_suppression")
     */
    public function suppressionTexte(
        Request $request,
        PrescriptionTexteListe $texteListe,
        PrescriptionTexteListeRepository $prescriptionTexteListeRepository
    ): Response {
        $form = $this->createForm(ReplacePrescriptionTexteListeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newTexte = $form->get('replacementText')->getData();

            if (!$newTexte instanceof PrescriptionTexteListe) {
                throw new \Exception("Le texte de remplacement n'est pas correcte.");
            }

            try {
                $prescriptionTexteListeRepository->replaceAndRemove($texteListe, $newTexte);
                $this->addFlash('success', 'Le texte a bien été supprimé');
            } catch (\Exception $e) {
                $this->addFlash('error', "Une erreur s'est produite lors de la suppression du texte (".$e->getMessage().')');
            }

            return $this->redirectToRoute('admin_prescriptions_textes');
        }

        return $this->render('prescriptions_textes/replacement_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
