<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\CommissionType;
use App\Form\CommissionsCollectionType;
use App\Repository\CommissionRepository;
use App\Repository\CommissionTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
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

    /** @var \Symfony\Component\Filesystem\Filesystem */
    private $filesystem;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var string */
    private $documentsRealDataPath;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommissionRepository $commissionRepository,
        CommissionTypeRepository $commissionTypeRepository,
        Filesystem $filesystem,
        LoggerInterface $logger,
        string $documentsRealDataPath
    ) {
        $this->entityManager = $entityManager;
        $this->commissionRepository = $commissionRepository;
        $this->commissionTypeRepository = $commissionTypeRepository;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->documentsRealDataPath = $documentsRealDataPath;
    }

    /**
     * @Route("/admin/commissions", name="admin_commissions_index")
     */
    public function index(Request $request): Response
    {
        $commissionTypes = $this->commissionTypeRepository->findAll();

        $selectedTypeId = $request->query->get('type');
        if (null === $selectedTypeId && count($commissionTypes) > 0) {
            $selectedTypeId = $commissionTypes[0]->getId();
        }

        return $this->render('commissions/index.html.twig', [
            'commission_types' => $commissionTypes,
            'selected_type_id' => $selectedTypeId,
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
            $commissionsToDelete = [];
            foreach ($commissions as $commission) {
                if (!in_array($commission->getId(), $submittedIds, true)) {
                    $commissionsToDelete[] = $commission->getId();
                    $this->entityManager->remove($commission);
                }
            }

            // Transaction Doctrine : flush database
            try {
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la sauvegarde des commissions : '.$e->getMessage());
                $this->addFlash('danger', 'Erreur lors de la sauvegarde des commissions.');

                return $this->redirectToRoute('admin_commissions_manage');
            }

            // SEULEMENT si le flush a réussi, on modifie le filesystem
            // Créer les dossiers pour les nouvelles commissions
            foreach ($submittedCommissions as $commission) {
                if ($commission->getId()) {
                    $commissionDir = $this->documentsRealDataPath.$commission->getId();

                    try {
                        $this->filesystem->mkdir($commissionDir);
                    } catch (IOException $IOException) {
                        $this->logger->error('Impossible de créer le dossier pour la commission.', [
                            'commission_dir' => $commissionDir,
                            'commission_id' => $commission->getId(),
                            'error' => $IOException->getMessage(),
                        ]);
                    }
                }
            }

            // Supprimer les dossiers des commissions supprimées
            foreach ($commissionsToDelete as $commissionId) {
                $commissionDir = $this->documentsRealDataPath.$commissionId;

                try {
                    $this->filesystem->remove($commissionDir);
                } catch (IOException $IOException) {
                    $this->logger->error('Impossible de supprimer le dossier pour la commission.', [
                        'commission_dir' => $commissionDir,
                        'commission_id' => $commission->getId(),
                        'error' => $IOException->getMessage(),
                    ]);
                }
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
}
