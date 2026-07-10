<?php

namespace App\Controller\Api;

use App\Entity\DossierType;
use App\Repository\DossierNatureListeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DossierController extends AbstractController
{
    private DossierNatureListeRepository $dossierNatureListeRepository;

    public function __construct(DossierNatureListeRepository $dossierNatureListeRepository)
    {
        $this->dossierNatureListeRepository = $dossierNatureListeRepository;
    }

    /**
     * API : Récupère les natures de dossier pour un type donné.
     *
     * @Route("/api/dossier/natures-by-type/{type}", name="api_dossier_natures_by_type", requirements={"type"="\d+"}, methods={"GET"}, options={"expose"=true})
     *
     * @param DossierType $type Type de dossier
     *
     * @return JsonResponse Liste des natures au format [{id, libelle}, ...]
     */
    public function getNaturesByType(DossierType $type): JsonResponse
    {
        $natures = $this->dossierNatureListeRepository->findByTypeOrderedByOrdre($type);

        return $this->json($natures, Response::HTTP_OK, [], [
            'groups' => 'api_response'
        ]);
    }
}
