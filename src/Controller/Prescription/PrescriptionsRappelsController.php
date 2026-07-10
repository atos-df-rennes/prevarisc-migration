<?php

namespace App\Controller\Prescription;

use App\Entity\PrescriptionReglementaire;
use App\Form\PrescriptionReglementaireType;
use App\Service\Prescriptions;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("gestion_prescriptions")
 */
class PrescriptionsRappelsController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    private Prescriptions $prescriptions;

    public function __construct(EntityManagerInterface $entityManager, Prescriptions $prescriptions)
    {
        $this->entityManager = $entityManager;
        $this->prescriptions = $prescriptions;
    }

    /**
     * @Route("/admin/prescriptions/rappels", name="admin_prescriptions_rappels")
     */
    public function index(): Response
    {
        return $this->render('prescriptions_rappels/index.html.twig', [
            'listePrescEtude' => $this->prescriptions->getPrescriptions('etude'),
            'listePrescVisite' => $this->prescriptions->getPrescriptions('visite'),
        ]);
    }

    /**
     * @Route("/admin/prescriptions/rappels/ajouter", name="admin_prescriptions_rappels_add")
     * @Route("/admin/prescriptions/rappels/modifier/{rappelReg}", name="admin_prescriptions_rappels_edit")
     */
    public function form(Request $request, ?PrescriptionReglementaire $rappelReg = null): Response
    {
        if (null === $rappelReg) {
            $rappelReg = new PrescriptionReglementaire();
        }

        $form = $this->createForm(PrescriptionReglementaireType::class, $rappelReg);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($rappelReg);
            $this->entityManager->flush();

            return $this->redirectToRoute('admin_prescriptions_rappels');
        }

        return $this->render('prescriptions_rappels/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
