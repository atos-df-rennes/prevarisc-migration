<?php

namespace App\Controller;

use App\Service\Prescriptions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
