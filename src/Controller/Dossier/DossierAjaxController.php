<?php

namespace App\Controller\Dossier;

use App\Repository\DossierRepository;
use App\Service\Dossier as ServiceDossier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pourrait aussi être rapatrié dans DossierController avec _dossier_lateral_nav: false.
 */
class DossierAjaxController extends AbstractController
{
    public function __construct(private readonly DossierRepository $dossierRepository, private readonly ServiceDossier $dossierService)
    {
    }

    #[Route(path: '/dossier/liste-enfants', name: 'dossier_liste_enfants')]
    public function getDossiersEnfants(Request $request): Response
    {
        $parentId = $request->request->getInt('parent');
        $parent = $parentId > 0 ? $this->dossierRepository->find($parentId) : null;
        if (null === $parent) {
            throw $this->createNotFoundException('Dossier introuvable.');
        }

        $this->denyAccessUnlessGranted('view_doss', $parent);
        $dossiersEnfants = $this->dossierRepository->getDossiersEnfants($parent);
        $dossiers = $this->dossierService->extraireDonneesUtiles($dossiersEnfants);

        return $this->render('dossier/_liste_enfants.html.twig', [
            'dossiers' => $dossiers
        ]);
    }
}
