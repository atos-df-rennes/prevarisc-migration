<?php

namespace App\Controller;

use App\Entity\PrescriptionReglementaire;
use App\Form\PrescriptionReglementaireType;
use App\Service\Prescriptions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PrescriptionsRappelsController extends AbstractController
{
    /**
     * @Route("/admin/prescriptions/rappels", name="admin_prescriptions_rappels")
     */
    public function index(Prescriptions $prescriptions): Response
    {
        return $this->render('prescriptions_rappels/index.html.twig', [
            'listePrescEtude' => $prescriptions->getPrescriptions('etude'),
            'listePrescVisite' => $prescriptions->getPrescriptions('visite'),
        ]);
    }

    /**
     * @Route("/admin/prescriptions/rappels/ajouter", name="admin_prescriptions_rappels_add" )
     */
    public function form(Request $request): Response
    {
        $form = $this->createForm(PrescriptionReglementaireType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $em = $this->getDoctrine()->getManager();
            $em->persist($data);
            $em->flush();

            return $this->redirectToRoute('admin_prescriptions_rappels');
        }

        return $this->render('prescriptions_rappels/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
