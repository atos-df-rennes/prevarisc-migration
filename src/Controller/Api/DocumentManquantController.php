<?php

namespace App\Controller\Api;

use App\Repository\DocumentManquantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

// @todo: Renvoyer directement les entités ?
class DocumentManquantController extends AbstractController
{
    /**
     * @Route("/api/document-manquant/liste", name="api_document_manquant_liste", methods={"GET"})
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
