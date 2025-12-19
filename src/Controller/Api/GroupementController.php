<?php

namespace App\Controller\Api;

use App\Repository\GroupementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API pour les groupements (autocomplete service instructeur).
 *
 * @Route("/api/groupements", name="api_groupements_")
 */
class GroupementController extends AbstractController
{
    /** @var GroupementRepository */
    private $groupementRepository;

    public function __construct(GroupementRepository $groupementRepository)
    {
        $this->groupementRepository = $groupementRepository;
    }

    /**
     * Recherche de groupements pour autocomplete.
     * Filtre uniquement les groupements de type "Service Instructeur" (ID 10).
     *
     * @Route("/search", name="search", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (!\is_string($query)) {
            throw new \InvalidArgumentException('Query must be a string.');
        }

        $limit = filter_var($request->query->get('limit', 50), FILTER_VALIDATE_INT);

        if (false === $limit) {
            throw new \InvalidArgumentException('Limit must be an integer.');
        }

        if (strlen($query) < 2) {
            return new JsonResponse([]);
        }

        // Filtrer uniquement les groupements de type "Service Instructeur" (ID 10)
        $groupements = $this->groupementRepository->searchByLibelle($query, $limit, 10);

        $results = [];
        foreach ($groupements as $groupement) {
            $results[] = [
                'id' => $groupement->getId(),
                'libelle' => $groupement->getLibelle(),
                'type' => $groupement->getType() ? $groupement->getType()->getLibelle() : null,
            ];
        }

        return new JsonResponse($results);
    }

    /**
     * Liste tous les groupements (pour initialisation).
     *
     * @Route("", name="list", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        $groupements = $this->groupementRepository->findAllOrdered();

        $results = [];
        foreach ($groupements as $groupement) {
            $results[] = [
                'id' => $groupement->getId(),
                'libelle' => $groupement->getLibelle(),
                'type' => $groupement->getType() ? $groupement->getType()->getLibelle() : null,
            ];
        }

        return new JsonResponse($results);
    }
}
