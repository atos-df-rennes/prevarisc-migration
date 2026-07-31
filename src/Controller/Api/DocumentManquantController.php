<?php

namespace App\Controller\Api;

use App\Repository\DocumentManquantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class DocumentManquantController extends AbstractController
{
    #[Route(path: '/api/document-manquant/liste', name: 'api_document_manquant_liste', methods: ['GET'])]
    public function liste(DocumentManquantRepository $repository): JsonResponse
    {
        $documents = $repository->findAll();

        return $this->json($documents);
    }
}
