<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Service\DossierPrescriptionService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DossierPrescriptionController extends AbstractController
{
    /**
     * @var DossierPrescriptionService
     */
    private $service;

    public function __construct(DossierPrescriptionService $service)
    {
        $this->service = $service;
    }

    /**
     * @Route("/dossier/{id}/prescriptions", name="dossier_prescriptions", requirements={"id"="\d+"}, methods={"GET"})
     *
     * @IsGranted("view_doss", subject="dossier")
     */
    public function index(Dossier $dossier): Response
    {
        return $this->render('dossier/prescriptions/index.html.twig', [
            'dossier' => $dossier,
            'prescriptionsRegl' => $this->service->getPrescriptionsParType($dossier, 0),
            'prescriptionsExploitation' => $this->service->getPrescriptionsParType($dossier, 1),
            'prescriptionsAmelioration' => $this->service->getPrescriptionsParType($dossier, 2),
        ]);
    }
}
