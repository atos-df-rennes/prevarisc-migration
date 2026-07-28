<?php

namespace App\Controller\Commission;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Commission;
use App\Enum\CommissionMembreCourrierType;
use App\Form\CommissionDocumentCrType;
use App\Form\CommissionMembreCourrierUploadType;
use App\Repository\CommissionMembreRepository;
use App\Service\Commission\CommissionDocumentCrService;
use App\Service\Commission\CommissionMembreCourrierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gère les onglets "Courriers types" et "Document type" d'une commission.
 */
#[IsGranted('gestion_commissions')]
class CommissionDocumentsController extends AbstractController
{
    public function __construct(private readonly CommissionDocumentCrService $commissionDocumentCrService, private readonly CommissionMembreCourrierService $commissionMembreCourrierService, private readonly CommissionMembreRepository $commissionMembreRepository, private readonly string $documentsCommissionDataPath, private readonly string $courriersMembresDataPath)
    {
    }

    /**
     * Onglet Courriers types des membres de la commission.
     */
    #[Route(path: '/admin/commissions/{id}/courriers', name: 'admin_commissions_courriers', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function courriers(Commission $commission): Response
    {
        $membres = $this->commissionMembreRepository->findBy(
            ['commission' => $commission]
        );

        /** @var array<int, array<string, mixed>> $membresWithForms */
        $membresWithForms = [];
        foreach ($membres as $membre) {
            $uploadForms = [];
            $filesExist = [];
            foreach (CommissionMembreCourrierType::ALL as $type) {
                $form = $this->createForm(CommissionMembreCourrierUploadType::class, null, [
                    'action' => $this->generateUrl('admin_commissions_courriers_upload', [
                        'id' => $commission->getId(),
                        'membreId' => $membre->getId(),
                        'type' => $type,
                    ]),
                ]);
                $uploadForms[$type] = $form->createView();
                $filesExist[$type] = $this->commissionMembreCourrierService->courrierExists($membre, $type);
            }

            $membresWithForms[] = [
                'membre' => $membre,
                'upload_forms' => $uploadForms,
                'files_exist' => $filesExist,
            ];
        }

        return $this->render('commissions/edit.html.twig', [
            'commission' => $commission,
            'active_tab' => 'courriers',
            'membres_with_forms' => $membresWithForms,
            'courriersMembresDataPath' => $this->courriersMembresDataPath,
        ]);
    }

    /**
     * Upload d'un courrier type pour un membre de commission.
     */
    #[Route(path: '/admin/commissions/{id}/courriers/{membreId}/{type}', name: 'admin_commissions_courriers_upload', requirements: ['id' => '\d+', 'membreId' => '\d+'], methods: ['POST'])]
    public function uploadCourrier(Commission $commission, int $membreId, string $type, Request $request): Response
    {
        if (!CommissionMembreCourrierType::isValid($type)) {
            $this->addFlash('danger', 'Type de courrier invalide.');

            return $this->redirectToRoute('admin_commissions_courriers', ['id' => $commission->getId()]);
        }

        $membre = $this->commissionMembreRepository->find($membreId);
        if (null === $membre || $membre->getCommission() !== $commission) {
            $this->addFlash('danger', 'Membre introuvable.');

            return $this->redirectToRoute('admin_commissions_courriers', ['id' => $commission->getId()]);
        }

        $form = $this->createForm(CommissionMembreCourrierUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $data */
            $data = $form->getData();
            /** @var UploadedFile $file */
            $file = $data['courrier'];

            try {
                $this->commissionMembreCourrierService->upload($membre, $type, $file);
                $this->addFlash('success', 'Le courrier a bien été sauvegardé.');
            } catch (\Exception) {
                $this->addFlash('danger', 'Le téléchargement a échoué.');
            }
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->redirectToRoute('admin_commissions_courriers', ['id' => $commission->getId()]);
    }

    /**
     * Suppression d'un courrier type d'un membre de commission.
     */
    #[Route(path: '/admin/commissions/{id}/courriers/{membreId}/{type}', name: 'admin_commissions_courriers_delete', requirements: ['id' => '\d+', 'membreId' => '\d+'], methods: ['DELETE'])]
    public function deleteCourrier(Commission $commission, int $membreId, string $type, Request $request): Response
    {
        /** @var string $token */
        $token = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('delete_courrier_'.$membreId.'_'.$type, $token)) {
            $this->addFlash('danger', 'Token de sécurité invalide.');

            return $this->redirectToRoute('admin_commissions_courriers', ['id' => $commission->getId()]);
        }

        if (!CommissionMembreCourrierType::isValid($type)) {
            $this->addFlash('danger', 'Type de courrier invalide.');

            return $this->redirectToRoute('admin_commissions_courriers', ['id' => $commission->getId()]);
        }

        $membre = $this->commissionMembreRepository->find($membreId);
        if (null === $membre || $membre->getCommission() !== $commission) {
            $this->addFlash('danger', 'Membre introuvable.');

            return $this->redirectToRoute('admin_commissions_courriers', ['id' => $commission->getId()]);
        }

        try {
            $this->commissionMembreCourrierService->delete($membre, $type);
            $this->addFlash('success', 'Le courrier a bien été supprimé.');
        } catch (\Exception) {
            $this->addFlash('danger', 'Erreur lors de la suppression du courrier.');
        }

        return $this->redirectToRoute('admin_commissions_courriers', ['id' => $commission->getId()]);
    }

    /**
     * Onglet Document type de la commission.
     * Gère l'affichage (GET) et l'upload (POST) du Compte Rendu type.
     */
    #[Route(path: '/admin/commissions/{id}/documents', name: 'admin_commissions_documents', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function documents(Commission $commission, Request $request): Response
    {
        $form = $this->createForm(CommissionDocumentCrType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $data */
            $data = $form->getData();
            /** @var UploadedFile $file */
            $file = $data['documentCr'];

            try {
                $this->commissionDocumentCrService->upload($commission, $file);
                $this->addFlash('success', 'Le document a bien été sauvegardé.');
            } catch (\Exception) {
                $this->addFlash('danger', 'Le téléchargement a échoué.');
            }

            return $this->redirectToRoute('admin_commissions_documents', ['id' => $commission->getId()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->render('commissions/edit.html.twig', [
            'commission' => $commission,
            'active_tab' => 'documents',
            'documentsCommissionDataPath' => $this->documentsCommissionDataPath,
            'documentCrExists' => $this->commissionDocumentCrService->documentCrExists($commission),
            'uploadForm' => $form,
        ]);
    }

    /**
     * Suppression du document type (Compte Rendu) de la commission.
     */
    #[Route(path: '/admin/commissions/{id}/documents', name: 'admin_commissions_documents_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteDocument(Commission $commission, Request $request): Response
    {
        /** @var string $token */
        $token = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('delete_commission_document_'.$commission->getId(), $token)) {
            $this->addFlash('danger', 'Token de sécurité invalide.');

            return $this->redirectToRoute('admin_commissions_documents', ['id' => $commission->getId()]);
        }

        try {
            $this->commissionDocumentCrService->delete($commission);
            $this->addFlash('success', 'Le document a bien été supprimé.');
        } catch (\Exception) {
            $this->addFlash('danger', 'Erreur lors de la suppression du document.');
        }

        return $this->redirectToRoute('admin_commissions_documents', ['id' => $commission->getId()]);
    }
}
