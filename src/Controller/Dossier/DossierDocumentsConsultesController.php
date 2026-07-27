<?php

namespace App\Controller\Dossier;

use App\Controller\Contract\DossierLateralNavigationInterface;
use App\Entity\Dossier;
use App\Entity\DossierDocumentConsulte;
use App\Entity\ListeDocumentAjoute;
use App\Entity\ListeDocumentConsulte;
use App\Form\Dossier\DossierDocumentConsulteType;
use App\Form\Dossier\ListeDocumentAjouteType;
use App\Service\Dossier\DossierDocumentsConsultesService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DossierDocumentsConsultesController extends AbstractController implements DossierLateralNavigationInterface
{
    public function __construct(private EntityManagerInterface $entityManager, private DossierDocumentsConsultesService $documentsConsultesService)
    {
    }

    /**
     * @Route("/dossier/{id}/documents-consultes", name="dossier_documents_consultes", methods={"GET"})
     *
     * @IsGranted("view_doss", subject="dossier")
     */
    public function index(Dossier $dossier): Response
    {
        $documentsConsultables = $this->documentsConsultesService->getDocumentsConsultables($dossier);

        // La collection documentsAjoutes est LAZY par défaut. Sans cet appel, Doctrine
        // émettra une requête par accès dans le template (N+1). On force le chargement ici.
        $dossier->getDocumentsAjoutes()->toArray();

        // Index des documents consultés par leur ID_DOC pour un lookup O(1) lors de la séparation.
        $documentsConsultesMap = [];
        foreach ($dossier->getDocumentsConsultes()->toArray() as $documentConsulte) {
            $documentsConsultesMap[$documentConsulte->getDocument()] = $documentConsulte;
        }

        // Conformément au legacy, les documents déjà renseignés apparaissent en tête de liste,
        // suivis des documents consultables non encore renseignés.
        $documentsConsultes = [];
        $documentsNonConsultes = [];
        foreach ($documentsConsultables as $doc) {
            $docId = (string) $doc->getId();
            if (isset($documentsConsultesMap[$docId])) {
                $documentsConsultes[] = ['doc' => $doc, 'documentConsulte' => $documentsConsultesMap[$docId]];
            } else {
                $documentsNonConsultes[] = $doc;
            }
        }

        return $this->render('dossier/documents_consultes/documents_consultes.html.twig', [
            'dossier' => $dossier,
            'documentsConsultes' => $documentsConsultes,
            'documentsNonConsultes' => $documentsNonConsultes,
        ]);
    }

    /**
     * @Route("/dossier/{id}/documents-consultes/{idDocumentConsultable}/ajouter",
     *     name="dossier_documents_consultes_add",
     *     methods={"GET", "POST"})
     *
     * @ParamConverter("documentConsultable", options={"id" = "idDocumentConsultable"})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function addDocumentConsulte(Request $request, Dossier $dossier, ListeDocumentConsulte $documentConsultable): Response
    {
        $documentConsulte = new DossierDocumentConsulte();
        $documentConsulte->setDossier($dossier);
        $documentConsulte->setDocument((string) $documentConsultable->getId());
        $documentConsulte->setConsulte(true);
        if (null !== $dossier->getNature()) {
            $documentConsulte->setNature($dossier->getNature());
        }

        $form = $this->createForm(DossierDocumentConsulteType::class, $documentConsulte);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($documentConsulte);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le document consulté a été ajouté avec succès.');

            return $this->redirectToRoute('dossier_documents_consultes', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/documents_consultes/edit_documents_consultes.html.twig', [
            'dossier' => $dossier,
            'documentConsulte' => $documentConsulte,
            'libelle' => $documentConsultable->getLibelle(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/dossier/{id}/documents-consultes/{idDocumentConsulte}/supprimer",
     *     name="dossier_documents_consultes_delete",
     *     requirements={"id"="\d+", "idDocumentConsulte"="\d+"},
     *     methods={"DELETE"},
     *     options={"_dossier_lateral_nav": false})
     *
     * @ParamConverter("documentConsulte", options={"id" = "idDocumentConsulte"})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function deleteDocumentConsulte(Request $request, Dossier $dossier, DossierDocumentConsulte $documentConsulte): Response
    {
        if ($documentConsulte->getDossier() !== $dossier) {
            throw $this->createAccessDeniedException('Ce document ne fait pas partie de ce dossier.');
        }

        $token = $request->request->get('token');
        if (!is_string($token) || !$this->isCsrfTokenValid('delete_document', $token)) {
            throw $this->createAccessDeniedException('Token CSRF non valide.');
        }

        $dossier->removeDocumentsConsulte($documentConsulte);
        $this->entityManager->flush();

        $this->addFlash('success', 'Le document consulté a été supprimé avec succès.');

        return $this->redirectToRoute('dossier_documents_consultes', ['id' => $dossier->getId()]);
    }

    /**
     * @Route("/dossier/{id}/documents-consultes/{idDocumentConsulte}/modifier",
     *     name="dossier_documents_consultes_edit",
     *     requirements={"id"="\d+", "idDocumentConsulte"="\d+"},
     *     methods={"GET", "POST"})
     *
     * @ParamConverter("documentConsulte", options={"id" = "idDocumentConsulte"})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function editDocumentConsulte(Request $request, Dossier $dossier, DossierDocumentConsulte $documentConsulte): Response
    {
        if ($documentConsulte->getDossier() !== $dossier) {
            throw $this->createAccessDeniedException('Ce document ne fait pas partie de ce dossier.');
        }

        $libelle = '';
        if (null !== $documentConsulte->getDocument() && 0 !== intval($documentConsulte->getDocument())) {
            $libelle = $this->documentsConsultesService->getLibelleDocumentConsulte($documentConsulte->getDocument());
        }

        $form = $this->createForm(DossierDocumentConsulteType::class, $documentConsulte);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Le document consulté a été modifié avec succès.');

            return $this->redirectToRoute('dossier_documents_consultes', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/documents_consultes/edit_documents_consultes.html.twig', [
            'dossier' => $dossier,
            'documentConsulte' => $documentConsulte,
            'libelle' => $libelle,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/dossier/{id}/documents-ajoutes/{idDocumentAjoute}/supprimer",
     *     name="dossier_documents_ajoutes_delete",
     *     requirements={"id"="\d+", "idDocumentAjoute"="\d+"},
     *     methods={"DELETE"},
     *     options={"_dossier_lateral_nav": false})
     *
     * @ParamConverter("documentAjoute", options={"id" = "idDocumentAjoute"})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function deleteDocumentAjoute(Request $request, Dossier $dossier, ListeDocumentAjoute $documentAjoute): Response
    {
        if ($documentAjoute->getDossier() !== $dossier) {
            throw $this->createAccessDeniedException('Ce document ne fait pas partie de ce dossier.');
        }

        $token = $request->request->get('token');
        if (!is_string($token) || !$this->isCsrfTokenValid('delete_document', $token)) {
            throw $this->createAccessDeniedException('Token CSRF non valide.');
        }

        $dossier->removeDocumentsAjoute($documentAjoute);
        $this->entityManager->flush();

        $this->addFlash('success', 'Le document a été supprimé avec succès.');

        return $this->redirectToRoute('dossier_documents_consultes', ['id' => $dossier->getId()]);
    }

    /**
     * @Route("/dossier/{id}/documents-ajoutes/ajouter",
     *     name="dossier_documents_ajoutes_add",
     *     requirements={"id"="\d+"},
     *     methods={"GET", "POST"})
     * @Route("/dossier/{id}/documents-ajoutes/{idDocumentAjoute}/modifier",
     *     name="dossier_documents_ajoutes_edit",
     *     requirements={"id"="\d+", "idDocumentAjoute"="\d+"},
     *     methods={"GET", "POST"})
     *
     * @ParamConverter("documentAjoute", options={"id" = "idDocumentAjoute"}, isOptional="true")
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function editDocumentAjoute(Request $request, Dossier $dossier, ?ListeDocumentAjoute $documentAjoute = null): Response
    {
        $isNew = null === $documentAjoute;

        if ($isNew) {
            $documentAjoute = new ListeDocumentAjoute();
            $documentAjoute->setDossier($dossier);
            if (null !== $dossier->getNature()) {
                $documentAjoute->setNature($dossier->getNature());
            }

            $this->entityManager->persist($documentAjoute);
        } elseif ($documentAjoute->getDossier() !== $dossier) {
            throw $this->createAccessDeniedException('Ce document ne fait pas partie de ce dossier.');
        }

        $form = $this->createForm(ListeDocumentAjouteType::class, $documentAjoute);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', $isNew ? 'Le document a été ajouté avec succès.' : 'Le document a été modifié avec succès.');

            return $this->redirectToRoute('dossier_documents_consultes', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/documents_consultes/edit_documents_ajoutes.html.twig', [
            'dossier' => $dossier,
            'documentAjoute' => $documentAjoute,
            'form' => $form->createView(),
        ]);
    }
}
