<?php

namespace App\Controller;

use App\Constant\TypePrescription;
use App\Entity\Dossier;
use App\Service\DossierPrescriptionService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
            'prescriptionsRegl' => $this->service->getPrescriptionsParType($dossier, TypePrescription::REGLEMENTAIRE),
            'prescriptionsExploitation' => $this->service->getPrescriptionsParType($dossier, TypePrescription::EXPLOITATION),
            'prescriptionsAmelioration' => $this->service->getPrescriptionsParType($dossier, TypePrescription::AMELIORATION),
        ]);
    }

    /**
     * @Route("/dossier/{id}/prescriptions/reordonner", name="dossier_prescriptions_reordonner", requirements={"id"="\d+"}, methods={"POST"}, options={"expose"=true})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function reordonner(Request $request, Dossier $dossier): Response
    {
        if ($dossier->hasVerrou()) {
            throw $this->createAccessDeniedException();
        }

        $rawIdsRegl = $request->request->get('idsRegl');
        $rawIdsAutres = $request->request->get('idsAutres');

        $idsRegl = array_filter(array_map('intval', explode(',', is_string($rawIdsRegl) ? $rawIdsRegl : '')));
        $idsAutres = array_filter(array_map('intval', explode(',', is_string($rawIdsAutres) ? $rawIdsAutres : '')));

        $this->service->reordonner($dossier, array_values($idsRegl), array_values($idsAutres));

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
