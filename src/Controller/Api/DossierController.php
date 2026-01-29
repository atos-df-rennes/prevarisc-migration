<?php

namespace App\Controller\Api;

use App\Entity\DossierType;
use App\Repository\DossierNatureListeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DossierController extends AbstractController
{
    /**
     * API : Récupère les natures de dossier pour un type donné.
     *
     * @Route("/api/dossier/natures-by-type/{type}", name="api_dossier_natures_by_type", requirements={"type"="\d+"}, methods={"GET"}, options={"expose"=true})
     *
     * @param DossierType                  $type                         Type de dossier
     * @param DossierNatureListeRepository $dossierNatureListeRepository Repository des natures
     *
     * @return JsonResponse Liste des natures au format [{id, libelle}, ...]
     */
    public function getNaturesByType(DossierType $type, DossierNatureListeRepository $dossierNatureListeRepository): JsonResponse
    {
        $natures = $dossierNatureListeRepository->findByTypeOrderedByOrdre($type);

        return $this->json($natures, 200, [], [
            'groups' => 'api_response'
        ]);
    }
}
