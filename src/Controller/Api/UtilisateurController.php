<?php

namespace App\Controller\Api;

use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

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
    #[Route(path: '/api/utilisateurs/search', name: 'api_utilisateurs_search', methods: ['GET'], options: ['expose' => true])]
    public function search(
        #[MapQueryParameter(name: 'q')] string $query = '',
        #[MapQueryParameter] int $limit = 100,
    ): JsonResponse {
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
