<?php

namespace App\Controller\Api;

use App\Repository\DocumentManquantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class DocumentManquantController extends AbstractController
{
    public function __construct(private readonly DocumentManquantRepository $repository)
    {
    }

    #[Route(path: '/api/document-manquant/liste', name: 'api_document_manquant_liste', methods: ['GET'])]
    public function liste(): JsonResponse
    {
        $documents = $this->repository->findAll();

        return $this->json($documents);
    }
}
