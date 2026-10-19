<?php

namespace App\Controller\Commission;

use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\Commission;
use App\Form\CommissionsCollectionType;
use App\Repository\CommissionRepository;
use App\Repository\CommissionTypeRepository;
use App\Service\Commission\CommissionManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('gestion_commissions')]
final class CommissionsController extends AbstractController
{
    public function __construct(private readonly CommissionRepository $commissionRepository, private readonly CommissionTypeRepository $commissionTypeRepository, private readonly CommissionManagerService $commissionManagerService)
    {
    }

    #[Route(path: '/admin/commissions', name: 'admin_commissions_index')]
    public function index(): Response
    {
        $commissionTypes = $this->commissionTypeRepository->findWithCommissions();

        return $this->render('commissions/index.html.twig', [
            'commission_types' => $commissionTypes,
        ]);
    }

    #[Route(path: '/admin/commissions/gerer', name: 'admin_commissions_manage')]
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
                $this->addFlash('danger', 'Erreur lors de la sauvegarde : '.$e->getMessage());

                return $this->redirectToRoute('admin_commissions_manage');
            }

            $this->addFlash('success', 'Les commissions ont été sauvegardées avec succès.');

            return $this->redirectToRoute('admin_commissions_index');
        }

        return $this->render('commissions/manage.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Redirige vers l'onglet compétences (point d'entrée générique par commission).
     */
    #[Route(path: '/admin/commissions/{id}', name: 'admin_commissions_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Commission $commission): RedirectResponse
    {
        return $this->redirectToRoute('admin_commissions_competences', ['id' => $commission->getId()]);
    }
}
