<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Enum\CommissionMembreCourrierType;
use App\Form\CommissionDocumentCrType;
use App\Form\CommissionMembreCourrierUploadType;
use App\Form\CommissionsCollectionType;
use App\Repository\CommissionMembreRepository;
use App\Repository\CommissionRepository;
use App\Repository\CommissionTypeRepository;
use App\Service\Commission\CommissionDocumentCrService;
use App\Service\Commission\CommissionManagerService;
use App\Service\Commission\CommissionMembreCourrierService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("admin")
 */
class CommissionsController extends AbstractController
{
    /** @var CommissionRepository */
    private $commissionRepository;

    /** @var CommissionTypeRepository */
    private $commissionTypeRepository;

    /** @var CommissionManagerService */
    private $commissionManagerService;

    /** @var CommissionDocumentCrService */
    private $commissionDocumentCrService;

    /** @var CommissionMembreCourrierService */
    private $commissionMembreCourrierService;

    /** @var CommissionMembreRepository */
    private $commissionMembreRepository;

    /** @var string */
    private $documentsCommissionDataPath;

    /** @var string */
    private $courriersMembresDataPath;

    public function __construct(
        CommissionRepository $commissionRepository,
        CommissionTypeRepository $commissionTypeRepository,
        CommissionManagerService $commissionManagerService,
        CommissionDocumentCrService $commissionDocumentCrService,
        CommissionMembreCourrierService $commissionMembreCourrierService,
        CommissionMembreRepository $commissionMembreRepository,
        string $documentsCommissionDataPath,
        string $courriersMembresDataPath
    ) {
        $this->commissionRepository = $commissionRepository;
        $this->commissionTypeRepository = $commissionTypeRepository;
        $this->commissionManagerService = $commissionManagerService;
        $this->commissionDocumentCrService = $commissionDocumentCrService;
        $this->commissionMembreCourrierService = $commissionMembreCourrierService;
        $this->commissionMembreRepository = $commissionMembreRepository;
        $this->documentsCommissionDataPath = $documentsCommissionDataPath;
        $this->courriersMembresDataPath = $courriersMembresDataPath;
    }

    /**
     * @Route("/admin/commissions", name="admin_commissions_index")
     */
    public function index(): Response
    {
        $commissionTypes = $this->commissionTypeRepository->findWithCommissions();

        return $this->render('commissions/index.html.twig', [
            'commission_types' => $commissionTypes,
        ]);
    }

    /**
     * @Route("/admin/commissions/gerer", name="admin_commissions_manage")
     */
    public function manage(Request $request): Response
    {
        $commissions = $this->commissionRepository->findAll();

        $form = $this->createForm(CommissionsCollectionType::class, [
            'commissions' => $commissions,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $data */
            $data = $form->getData();
            /** @var Commission[] $submittedCommissions */
            $submittedCommissions = $data['commissions'] ?? [];

            try {
                $this->commissionManagerService->saveCommissions($commissions, $submittedCommissions);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la sauvegarde : '.$e->getMessage());

                return $this->redirectToRoute('admin_commissions_manage');
            }

            $this->addFlash('success', 'Les commissions ont été sauvegardées avec succès.');

            return $this->redirectToRoute('admin_commissions_index');
        }

        return $this->render('commissions/manage.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Redirige vers l'onglet compétences (point d'entrée générique par commission).
     *
     * @Route("/admin/commissions/{id}", name="admin_commissions_show", requirements={"id"="\d+"}, methods={"GET"})
     */
    public function show(Commission $commission): Response
    {
        return $this->redirectToRoute('admin_commissions_competences', ['id' => $commission->getId()]);
    }

    /**
     * Onglet Membres (placeholder démo).
     *
     * @Route("/admin/commissions/{id}/membres", name="admin_commissions_membres", requirements={"id"="\d+"}, methods={"GET"})
     */
    public function membres(Commission $commission): Response
    {
        return $this->render('commissions/edit.html.twig', [
            'commission' => $commission,
            'active_tab' => 'membres',
        ]);
    }

    /**
     * Onglet Contacts (placeholder démo).
     *
     * @Route("/admin/commissions/{id}/contacts", name="admin_commissions_contacts", requirements={"id"="\d+"}, methods={"GET"})
     */
    public function contacts(Commission $commission): Response
    {
        return $this->render('commissions/edit.html.twig', [
            'commission' => $commission,
            'active_tab' => 'contacts',
        ]);
    }

    /**
     * Onglet Courriers types des membres de la commission.
     *
     * @Route("/admin/commissions/{id}/courriers", name="admin_commissions_courriers", requirements={"id"="\d+"}, methods={"GET"})
     */
    public function courriers(Commission $commission): Response
    {
        $membres = $this->commissionMembreRepository->findBy(
            ['commission' => $commission]
        );

        /** @var array<int, array<string, mixed>> $membresWithForms */
        $membresWithForms = [];
        foreach ($membres as $membre) {
            $uploadForms = [];
            foreach (CommissionMembreCourrierType::ALL as $type) {
                $form = $this->createForm(CommissionMembreCourrierUploadType::class, null, [
                    'action' => $this->generateUrl('admin_commissions_courriers_upload', [
                        'id' => $commission->getId(),
                        'membreId' => $membre->getId(),
                        'type' => $type,
                    ]),
                ]);
                $uploadForms[$type] = $form->createView();
            }

            $membresWithForms[] = [
                'membre' => $membre,
                'upload_forms' => $uploadForms,
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
     *
     * @Route("/admin/commissions/{id}/courriers/{membreId}/{type}", name="admin_commissions_courriers_upload", requirements={"id"="\d+","membreId"="\d+"}, methods={"POST"})
     */
    public function uploadCourrier(Commission $commission, int $membreId, string $type, Request $request): Response
    {
        if (!CommissionMembreCourrierType::isValid($type)) {
            $this->addFlash('error', 'Type de courrier invalide.');

            return $this->redirectToRoute('admin_commissions_courriers', ['id' => $commission->getId()]);
        }

        $membre = $this->commissionMembreRepository->find($membreId);
        if (null === $membre || $membre->getCommission() !== $commission) {
            $this->addFlash('error', 'Membre introuvable.');

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
            } catch (\Exception $e) {
                $this->addFlash('error', 'Le téléchargement a échoué.');
            }
        }

        return $this->redirectToRoute('admin_commissions_courriers', ['id' => $commission->getId()]);
    }

    /**
     * Suppression d'un courrier type d'un membre de commission.
     *
     * @Route("/admin/commissions/{id}/courriers/{membreId}/{type}", name="admin_commissions_courriers_delete", requirements={"id"="\d+","membreId"="\d+"}, methods={"DELETE"})
     */
    public function deleteCourrier(Commission $commission, int $membreId, string $type, Request $request): Response
    {
        /** @var string $token */
        $token = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('delete_courrier_'.$membreId.'_'.$type, $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');

            return $this->redirectToRoute('admin_commissions_courriers', ['id' => $commission->getId()]);
        }

        if (!CommissionMembreCourrierType::isValid($type)) {
            $this->addFlash('error', 'Type de courrier invalide.');

            return $this->redirectToRoute('admin_commissions_courriers', ['id' => $commission->getId()]);
        }

        $membre = $this->commissionMembreRepository->find($membreId);
        if (null === $membre || $membre->getCommission() !== $commission) {
            $this->addFlash('error', 'Membre introuvable.');

            return $this->redirectToRoute('admin_commissions_courriers', ['id' => $commission->getId()]);
        }

        try {
            $this->commissionMembreCourrierService->delete($membre, $type);
            $this->addFlash('success', 'Le courrier a bien été supprimé.');
        } catch (\Exception $exception) {
            $this->addFlash('error', 'Erreur lors de la suppression du courrier.');
        }

        return $this->redirectToRoute('admin_commissions_courriers', ['id' => $commission->getId()]);
    }

    /**
     * Onglet Document type de la commission.
     * Gère l'affichage (GET) et l'upload (POST) du Compte Rendu type.
     *
     * @Route("/admin/commissions/{id}/documents", name="admin_commissions_documents", requirements={"id"="\d+"}, methods={"GET", "POST"})
     */
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
            } catch (\Exception $e) {
                $this->addFlash('error', 'Le téléchargement a échoué.');
            }

            return $this->redirectToRoute('admin_commissions_documents', ['id' => $commission->getId()]);
        }

        return $this->render('commissions/edit.html.twig', [
            'commission' => $commission,
            'active_tab' => 'documents',
            'documentsCommissionDataPath' => $this->documentsCommissionDataPath,
            'uploadForm' => $form->createView(),
        ]);
    }

    /**
     * Suppression du document type (Compte Rendu) de la commission.
     *
     * @Route("/admin/commissions/{id}/documents", name="admin_commissions_documents_delete", requirements={"id"="\d+"}, methods={"DELETE"})
     */
    public function deleteDocument(Commission $commission, Request $request): Response
    {
        /** @var string $token */
        $token = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('delete_commission_document_'.$commission->getId(), $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');

            return $this->redirectToRoute('admin_commissions_documents', ['id' => $commission->getId()]);
        }

        try {
            $this->commissionDocumentCrService->delete($commission);
            $this->addFlash('success', 'Le document a bien été supprimé.');
        } catch (\Exception $exception) {
            $this->addFlash('error', 'Erreur lors de la suppression du document.');
        }

        return $this->redirectToRoute('admin_commissions_documents', ['id' => $commission->getId()]);
    }
}
