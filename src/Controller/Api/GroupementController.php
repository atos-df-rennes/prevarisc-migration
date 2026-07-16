<?php

namespace App\Controller\Api;

use App\Repository\GroupementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API pour les groupements (autocomplete service instructeur).
 */
class GroupementController extends AbstractController
{
    private GroupementRepository $groupementRepository;

    public function __construct(GroupementRepository $groupementRepository)
    {
        $this->groupementRepository = $groupementRepository;
    }

    /**
     * Recherche de groupements pour autocomplete.
     * Filtre uniquement les groupements de type "Service Instructeur" (ID 10).
     *
     * @Route("/api/groupements/search", name="api_groupements_search", methods={"GET"})
     */
    public function search(Request $request): JsonResponse
    {
        $query = (string) $request->query->get('q', '');

        $limit = filter_var($request->query->get('limit', 50), FILTER_VALIDATE_INT);

        if (false === $limit) {
            $limit = 50;
        }

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
     *
     * @Route("/api/groupements", name="api_groupements_list", methods={"GET"})
     */
    public function list(): JsonResponse
    {
        $groupements = $this->groupementRepository->findAllOrdered();

        return $this->json($groupements, Response::HTTP_OK, [], [
            'groups' => 'api_response',
        ]);
    }
}
