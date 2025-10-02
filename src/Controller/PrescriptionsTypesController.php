<?php

namespace App\Controller;

use App\Repository\PrescriptionCategorieRepository;
use App\Service\Prescriptions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("admin")
 */
class PrescriptionsTypesController extends AbstractController
{
    /**
     * @Route("/admin/prescriptions", name="admin_prescriptions")
     */
    public function index(Prescriptions $prescriptions, PrescriptionCategorieRepository $prescriptionCategorieRepository): Response
    {
        return $this->render('prescriptions_types/index.html.twig', [
            'categories' => $prescriptionCategorieRepository->findAll(),
            'sans_categories' => $prescriptions->showPrescriptionType(0, 0, 0),
        ]);
    }
}
