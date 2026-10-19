<?php

namespace App\Controller\Api;

use App\Repository\GroupementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API pour les groupements (autocomplete service instructeur).
 */
final class GroupementController extends AbstractController
{
    public function __construct(private readonly GroupementRepository $groupementRepository)
    {
    }

    /**
     * Recherche de groupements pour autocomplete.
     * Filtre uniquement les groupements de type "Service Instructeur" (ID 10).
     */
    #[Route(path: '/api/groupements/search', name: 'api_groupements_search', methods: ['GET'], options: ['expose' => true])]
    public function search(
        #[MapQueryParameter(name: 'q')] string $query = '',
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $limit = 50,
    ): JsonResponse {
        $limit ??= 50;
        $limit = max(1, min(50, $limit));

        // Filtrer uniquement les groupements de type "Service Instructeur" (ID 10).
        // Une requête vide retourne les N premiers services instructeurs (pour le preload TomSelect).
        // Un seul caractère est insuffisant pour filtrer utilement et génère des requêtes inutiles.
        if ('' !== $query && strlen($query) < 2) {
            return new JsonResponse([]);
        }

        $groupements = $this->groupementRepository->searchByLibelle($query, $limit, 10);

        return $this->json($groupements, Response::HTTP_OK, [], [
            'groups' => 'api_response',
        ]);
    }

    /**
     * Liste tous les groupements (pour initialisation).
     */
    #[Route(path: '/api/groupements', name: 'api_groupements_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $groupements = $this->groupementRepository->findAllOrdered();

        return $this->json($groupements, Response::HTTP_OK, [], [
            'groups' => 'api_response',
        ]);
    }
}
