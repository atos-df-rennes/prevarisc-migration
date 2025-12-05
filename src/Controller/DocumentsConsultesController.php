<?php

namespace App\Controller;

use App\Entity\ListeDocumentConsulte;
use App\Form\ListeDocumentConsulteType;
use App\Repository\ListeDocumentConsulteRepository;
use App\Service\DocumentsConsultes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DocumentsConsultesController extends AbstractController
{
    /**
     * @Route("/admin/documents-consultes", name="admin_documents_consultes")
     */
    public function index(ListeDocumentConsulteRepository  $listeDocumentConsulteRepository): Response
    {
        $documentsConsultes = $listeDocumentConsulteRepository->findAllSorted();

        return $this->render('documents_consultes/index.html.twig', [
            'documents' => $documentsConsultes,
        ]);
    }

    /**
     * @Route("/admin/documents-consultes/add", name="admin_documents_consultes_add")
     */
    public function ajouter(Request $request, DocumentsConsultes $serviceDocumentsConsultes): Response
    {
        $documentConsulte = new ListeDocumentConsulte();

        $form = $this->createForm(ListeDocumentConsulteType::class, $documentConsulte, [
            'action' => $this->generateUrl('admin_documents_consultes_add'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $serviceDocumentsConsultes->save($documentConsulte);

            $this->addFlash('success', 'Document consulté ajouté.');

            return $this->redirectToRoute('admin_documents_consultes');
        }

        return $this->render('documents_consultes/ajout.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/admin/documents-consultes/{id}/edit", name="admin_documents_consultes_edit")
     */
    public function modifier(Request $request, ListeDocumentConsulte $documentConsulte, DocumentsConsultes $serviceDocumentConsulte): Response
    {
        $form = $this->createForm(ListeDocumentConsulteType::class, $documentConsulte, [
            'action' => $this->generateUrl('admin_documents_consultes_edit', ['id' => $documentConsulte->getId()]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $serviceDocumentConsulte->save($documentConsulte);

            $this->addFlash('success', 'Document consulté modifié.');

            return $this->redirectToRoute('admin_documents_consultes');
        }

        return $this->render('documents_consultes/ajout.html.twig', [
            'form' => $form->createView(),
            'edit' => true,
        ]);
    }
}
