<?php

namespace App\Controller\Api;

use App\Repository\DocumentManquantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/document-manquant", name="api_document_manquant_")
 */
class DocumentManquantController extends AbstractController
{
    /**
     * @Route("/liste", name="liste", methods={"GET"})
     */
    public function liste(DocumentManquantRepository $repository): JsonResponse
    {
        $documents = $repository->findAll();
        
        $data = [];
        foreach ($documents as $doc) {
            $data[] = [
                'id' => $doc->getId(),
                'libelle' => $doc->getLibelle(),
            ];
        }
        
        return $this->json($data);
    }
}
