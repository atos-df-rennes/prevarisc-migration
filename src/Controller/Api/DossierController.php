<?php

namespace App\Controller\Api;

use App\Entity\DossierType;
use App\Repository\DossierNatureListeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DossierController extends AbstractController
{
    public function __construct(private readonly DossierNatureListeRepository $dossierNatureListeRepository)
    {
    }

    /**
     * API : Récupère les natures de dossier pour un type donné.
     *
     * @param DossierType $type Type de dossier
     *
     * @return JsonResponse Liste des natures au format [{id, libelle}, ...]
     */
    #[Route(path: '/api/dossier/natures-by-type/{type}', name: 'api_dossier_natures_by_type', requirements: ['type' => '\d+'], methods: ['GET'], options: ['expose' => true])]
    public function getNaturesByType(DossierType $type): JsonResponse
    {
        $natures = $this->dossierNatureListeRepository->findByTypeOrderedByOrdre($type);

        return $this->json($natures, Response::HTTP_OK, [], [
            'groups' => 'api_response'
        ]);
    }
}
