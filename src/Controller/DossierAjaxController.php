<?php

namespace App\Controller;

use App\Repository\DossierRepository;
use App\Service\Dossier as ServiceDossier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Pourrait aussi être rapatrié dans DossierController avec _dossier_lateral_nav: false.
 */
class DossierAjaxController extends AbstractController
{
    /** @var DossierRepository */
    private $dossierRepository;

    /** @var ServiceDossier */
    private $dossierService;

    public function __construct(DossierRepository $dossierRepository, ServiceDossier $dossierService)
    {
        $this->dossierRepository = $dossierRepository;
        $this->dossierService = $dossierService;
    }

    /**
     * @Route("/dossier/liste-enfants", name="dossier_liste_enfants")
     */
    public function getDossiersEnfants(Request $request): Response
    {
        $parentId = $request->request->getInt('parent');
        $dossiers = [];
        if ($parentId > 0) {
            $dossiersEnfants = $this->dossierRepository->getDossiersEnfants($parentId);
            $dossiers = $this->dossierService->extraireDonneesUtiles($dossiersEnfants);
        }

        return $this->render('dossier/_liste_enfants.html.twig', [
            'dossiers' => $dossiers
        ]);
    }
}
