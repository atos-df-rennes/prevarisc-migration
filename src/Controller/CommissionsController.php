<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\CommissionType;
use App\Form\CommissionsCollectionType;
use App\Repository\CommissionRepository;
use App\Repository\CommissionTypeRepository;
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

    /** @var string */
    private $documentsRealDataPath;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommissionRepository $commissionRepository,
        CommissionTypeRepository $commissionTypeRepository,
        string $documentsRealDataPath
    ) {
        $this->entityManager = $entityManager;
        $this->commissionRepository = $commissionRepository;
        $this->commissionTypeRepository = $commissionTypeRepository;
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

            // Supprimer les commissions absentes du formulaire (gestion explicite car collection non liée à entité)
            foreach ($commissions as $commission) {
                if (!in_array($commission->getId(), $submittedIds, true)) {
                    $this->entityManager->remove($commission);
                }
            }

            $this->entityManager->flush();

            // Créer le dossier de documents pour les nouvelles commissions
            foreach ($submittedCommissions as $commission) {
                if ($commission->getId()) {
                    $commissionDir = $this->documentsRealDataPath.$commission->getId();

                    if (!is_dir($commissionDir)) {
                        mkdir($commissionDir);
                    }
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
