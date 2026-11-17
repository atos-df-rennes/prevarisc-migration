<?php

namespace App\Controller\Prescription;

use App\Entity\PrescriptionReglementaire;
use App\Form\PrescriptionReglementaireType;
use App\Service\Prescriptions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('gestion_prescriptions')]
class PrescriptionsRappelsController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly Prescriptions $prescriptions)
    {
    }

    #[Route(path: '/admin/prescriptions/rappels', name: 'admin_prescriptions_rappels')]
    public function index(): Response
    {
        return $this->render('prescriptions_rappels/index.html.twig', [
            'listePrescEtude' => $this->prescriptions->getPrescriptions('etude'),
            'listePrescVisite' => $this->prescriptions->getPrescriptions('visite'),
        ]);
    }

    #[Route(path: '/admin/prescriptions/rappels/ajouter', name: 'admin_prescriptions_rappels_add')]
    #[Route(path: '/admin/prescriptions/rappels/modifier/{rappelReg}', name: 'admin_prescriptions_rappels_edit')]
    public function form(Request $request, ?PrescriptionReglementaire $rappelReg = null): Response
    {
        $rappelReg ??= new PrescriptionReglementaire();

        $form = $this->createForm(PrescriptionReglementaireType::class, $rappelReg);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($rappelReg);
            $this->entityManager->flush();

            return $this->redirectToRoute('admin_prescriptions_rappels');
        }

        return $this->render('prescriptions_rappels/form.html.twig', [
            'form' => $form,
            'rappel_reg' => $rappelReg,
        ]);
    }
}
