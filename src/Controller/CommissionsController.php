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
     * Page dédiée d'édition des règles de compétence d'une commission.
     * Remplace l'ancienne approche AJAX fragmentée.
     *
     * @Route("/admin/commissions/{id}", name="admin_commissions_edit", requirements={"id"="\d+"}, methods={"GET", "POST"})
     */
    public function edit(Commission $commission, Request $request): Response
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

            // Supprimer les règles retirées du formulaire
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

            // Persister les règles soumises
            foreach ($submittedRegles as $regle) {
                $regle->setCommission($commission);
                $this->entityManager->persist($regle);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Les règles de compétence ont été sauvegardées avec succès.');

            return $this->redirectToRoute('admin_commissions_edit', ['id' => $commission->getId()]);
        }

        return $this->render('commissions/edit.html.twig', [
            'commission' => $commission,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/commissions/{id}/competences", name="admin_commissions_competences", requirements={"id"="\d+"}, methods={"GET", "POST"})
     *
     * @deprecated utiliser admin_commissions_edit à la place
     */
    public function competences(Commission $commission): Response
    {
        return $this->redirectToRoute('admin_commissions_edit', ['id' => $commission->getId()], 301);
    }
}
