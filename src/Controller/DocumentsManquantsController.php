<?php

namespace App\Controller;

use App\Entity\DocumentManquant;
use App\Form\DocumentManquantType;
use App\Repository\DocumentManquantRepository;
use App\Service\DocumentsManquants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DocumentsManquantsController extends AbstractController
{
    /**
     * @Route("/admin/documents-manquants", name="admin_documents_manquants")
     */
    public function index(DocumentManquantRepository $documentManquantRepository): Response
    {
        $documentsManquants = $documentManquantRepository->findAllSorted();

        return $this->render('documents_manquants/index.html.twig', [
            'documentsManquants' => $documentsManquants,
        ]);
    }

    /**
     * @Route("/admin/documents-manquants/add", name="admin_documents_manquants_add")
     */
    public function ajouter(Request $request, DocumentsManquants $serviceDocumentsManquants): Response
    {
        $documentManquant = new DocumentManquant();

        $form = $this->createForm(DocumentManquantType::class, $documentManquant, [
            'action' => $this->generateUrl('admin_documents_manquants_add'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $serviceDocumentsManquants->create($documentManquant);

            $this->addFlash('success', 'Document manquant ajouté.');

            return $this->redirectToRoute('admin_documents_manquants');
        }

        return $this->render('documents_manquants/ajout.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
