<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Form\CommissionsCollectionType;
use App\Repository\CommissionRepository;
use App\Repository\CommissionTypeRepository;
use App\Service\Commission\CommissionManagerService;
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
    /** @var CommissionRepository */
    private $commissionRepository;

    /** @var CommissionTypeRepository */
    private $commissionTypeRepository;

    /** @var CommissionManagerService */
    private $commissionManagerService;

    public function __construct(
        CommissionRepository $commissionRepository,
        CommissionTypeRepository $commissionTypeRepository,
        CommissionManagerService $commissionManagerService
    ) {
        $this->commissionRepository = $commissionRepository;
        $this->commissionTypeRepository = $commissionTypeRepository;
        $this->commissionManagerService = $commissionManagerService;
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
}
