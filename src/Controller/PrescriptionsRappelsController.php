<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PrescriptionsRappelsController extends AbstractController
{
    /**
     * @Route("/admin/prescriptions/rappels", name="admin_prescriptions_rappels")
     */
    public function index(): Response
    {

//        $this->view->assign('listePrescEtude', $service_prescription->getPrescriptions('etude'));
//        $this->view->assign('listePrescVisite', $service_prescription->getPrescriptions('visite'));


        return $this->render('prescriptions_rappels/index.html.twig', [
            'listePrescEtude' => [],
            'listePrescVisite' => [],
        ]);
    }
}
