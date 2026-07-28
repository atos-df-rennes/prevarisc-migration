<?php

namespace App\Controller\Api;

use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API pour les utilisateurs (autocomplete préventionnistes).
 */
class UtilisateurController extends AbstractController
{
    public function __construct(private readonly UtilisateurRepository $utilisateurRepository)
    {
    }

    /**
     * Recherche de préventionnistes pour autocomplete.
     * Conforme au legacy /api/1.0/search/users (lignes 81-129).
     */
    #[Route(path: '/api/utilisateurs/search', name: 'api_utilisateurs_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = (string) $request->query->get('q', '');

        $limit = filter_var($request->query->get('limit', 100), FILTER_VALIDATE_INT);

        if (false === $limit) {
            throw new \InvalidArgumentException('Limit must be an integer.');
        }

        // Minimum 3 caractères pour lancer la recherche (comme legacy minLength: 3)
        if (strlen($query) < 3) {
            return new JsonResponse([]);
        }

        // Rechercher uniquement les préventionnistes actifs
        $utilisateurs = $this->utilisateurRepository->searchPreventionnistes($query, $limit, true);

        return $this->json($utilisateurs, Response::HTTP_OK, [], [
            'groups' => 'api_response',
        ]);
    }
}
