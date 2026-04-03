<?php

namespace App\Controller\Api;

use App\Repository\EtablissementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API pour les établissements (autocomplete).
 */
class EtablissementController extends AbstractController
{
    /** @var EtablissementRepository */
    private $etablissementRepository;

    public function __construct(EtablissementRepository $etablissementRepository)
    {
        $this->etablissementRepository = $etablissementRepository;
    }

    /**
     * Recherche d'établissements par libellé pour autocomplete.
     *
     * @Route("/api/etablissements/search", name="api_etablissements_search", methods={"GET"})
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (!is_string($query) || strlen($query) < 2) {
            return new JsonResponse([]);
        }

        $limit = filter_var($request->query->get('limit', 20), FILTER_VALIDATE_INT);
        if (false === $limit) {
            $limit = 20;
        }

        $etablissements = $this->etablissementRepository->searchByLibelle($query, $limit);

        $results = [];
        foreach ($etablissements as $etab) {
            $info = $etab->getLatestInformations();
            $genre = null !== $info && null !== $info->getGenre() ? $info->getGenre()->getLibelle() : null;
            $results[] = [
                'id' => $etab->getId(),
                'libelle' => null !== $info ? $info->getLibelle() : '',
                'genre' => $genre,
            ];
        }

        return new JsonResponse($results, Response::HTTP_OK);
    }
}
