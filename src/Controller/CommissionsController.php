<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\CommissionRegle;
use App\Entity\CommissionType;
use App\Form\CommissionReglesCollectionType;
use App\Form\CommissionsCollectionType;
use App\Repository\CommissionRegleRepository;
use App\Repository\CommissionRepository;
use App\Repository\CommissionTypeRepository;
use App\Service\Commission\CommissionFilesystem;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("admin")
 */
class CommissionsController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var CommissionRepository */
    private $commissionRepository;

    /** @var CommissionTypeRepository */
    private $commissionTypeRepository;

    /** @var CommissionFilesystem */
    private $commissionFilesystem;

    /** @var CommissionRegleRepository */
    private $commissionRegleRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommissionRepository $commissionRepository,
        CommissionTypeRepository $commissionTypeRepository,
        CommissionFilesystem $commissionFilesystem,
        CommissionRegleRepository $commissionRegleRepository
    ) {
        $this->entityManager = $entityManager;
        $this->commissionRepository = $commissionRepository;
        $this->commissionTypeRepository = $commissionTypeRepository;
        $this->commissionFilesystem = $commissionFilesystem;
        $this->commissionRegleRepository = $commissionRegleRepository;
    }

    /**
     * @Route("/admin/commissions", name="admin_commissions_index")
     */
    public function index(): Response
    {
        $commissionTypes = $this->commissionTypeRepository->findAll();

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

            // Récupérer les IDs des commissions soumises
            $submittedIds = [];
            foreach ($submittedCommissions as $commission) {
                $this->entityManager->persist($commission);

                if ($commission->getId()) {
                    $submittedIds[] = $commission->getId();
                }
            }

            // Identifier les commissions à supprimer AVANT le flush
            /** @var int[] $commissionsToDelete */
            $commissionsToDelete = [];
            foreach ($commissions as $commission) {
                $commissionId = $commission->getId();
                if ($commissionId && !in_array($commissionId, $submittedIds, true)) {
                    $commissionsToDelete[] = $commissionId;
                    $this->entityManager->remove($commission);
                }
            }

            // Transaction Doctrine : flush database
            try {
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la sauvegarde : '.$e->getMessage());

                return $this->redirectToRoute('admin_commissions_manage');
            }

            // SEULEMENT si le flush a réussi, on modifie le filesystem
            // Créer les dossiers pour les nouvelles commissions
            foreach ($submittedCommissions as $commission) {
                if ($commission->getId()) {
                    $this->commissionFilesystem->createCommissionDirectory($commission->getId());
                }
            }

            // Supprimer les dossiers des commissions supprimées
            foreach ($commissionsToDelete as $commissionId) {
                $this->commissionFilesystem->removeCommissionDirectory($commissionId);
            }

            $this->addFlash('success', 'Les commissions ont été sauvegardées avec succès.');

            return $this->redirectToRoute('admin_commissions_index');
        }

        return $this->render('commissions/manage.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/commissions/liste/{typeId}", name="admin_commissions_list", requirements={"typeId"="\d+"})
     */
    public function getCommissions(CommissionType $typeId): Response
    {
        $commissions = $this->commissionRepository->findBy(
            ['type' => $typeId],
            ['libelle' => 'ASC']
        );

        return $this->render('commissions/_list.html.twig', [
            'commissions' => $commissions,
            'commission_type' => $typeId,
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
     * Onglet Compétences — édition des règles de compétence d'une commission.
     *
     * @Route("/admin/commissions/{id}/competences", name="admin_commissions_competences", requirements={"id"="\d+"}, methods={"GET", "POST"})
     */
    public function competences(Commission $commission, Request $request): Response
    {
        $regles = $this->commissionRegleRepository->findByCommissionWithRelations($commission);

        $form = $this->createForm(CommissionReglesCollectionType::class, ['regles' => $regles], [
            'commission' => $commission,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $data */
            $data = $form->getData();
            /** @var CommissionRegle[] $submittedRegles */
            $submittedRegles = $data['regles'] ?? [];

            $submittedIds = [];
            foreach ($submittedRegles as $regle) {
                $regleId = $regle->getId();
                if ($regleId) {
                    $submittedIds[] = $regleId;
                }
            }

            foreach ($regles as $existingRegle) {
                $existingId = $existingRegle->getId();
                if ($existingId && !in_array($existingId, $submittedIds, true)) {
                    $this->entityManager->remove($existingRegle);
                }
            }

            foreach ($submittedRegles as $regle) {
                $regle->setCommission($commission);
                $this->entityManager->persist($regle);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Les règles de compétence ont été sauvegardées avec succès.');

            return $this->redirectToRoute('admin_commissions_competences', ['id' => $commission->getId()]);
        }

        return $this->render('commissions/edit.html.twig', [
            'commission' => $commission,
            'active_tab' => 'competences',
            'form' => $form->createView(),
        ]);
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
     * Onglet Courriers (placeholder démo).
     *
     * @Route("/admin/commissions/{id}/courriers", name="admin_commissions_courriers", requirements={"id"="\d+"}, methods={"GET"})
     */
    public function courriers(Commission $commission): Response
    {
        return $this->render('commissions/edit.html.twig', [
            'commission' => $commission,
            'active_tab' => 'courriers',
        ]);
    }

    /**
     * Onglet Documents (placeholder démo).
     *
     * @Route("/admin/commissions/{id}/documents", name="admin_commissions_documents", requirements={"id"="\d+"}, methods={"GET"})
     */
    public function documents(Commission $commission): Response
    {
        return $this->render('commissions/edit.html.twig', [
            'commission' => $commission,
            'active_tab' => 'documents',
        ]);
    }
}
