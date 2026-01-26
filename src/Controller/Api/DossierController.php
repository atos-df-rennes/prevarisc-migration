<?php

namespace App\Controller\Api;

use App\Repository\DossierNatureListeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DossierController extends AbstractController
{
    /**
     * API : Récupère les natures de dossier pour un type donné.
     *
     * @Route("/api/dossier/natures-by-type/{typeId}", name="api_dossier_natures_by_type", requirements={"typeId"="\d+"}, methods={"GET"})
     *
     * @param int                          $typeId                       ID du type de dossier
     * @param DossierNatureListeRepository $dossierNatureListeRepository Repository des natures
     *
     * @return JsonResponse Liste des natures au format [{id, libelle}, ...]
     */
    public function getNaturesByType(int $typeId, DossierNatureListeRepository $dossierNatureListeRepository): JsonResponse
    {
        $natures = $dossierNatureListeRepository->findByTypeOrderedByOrdre($typeId);

        return $this->json($natures, 200, [], [
            'groups' => 'api_response'
        ]);
    }
}
