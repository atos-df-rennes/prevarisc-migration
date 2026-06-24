<?php

namespace App\Controller\Commission;

use App\Entity\Commission;
use App\Form\CommissionMembresCollectionType;
use App\Repository\CommissionMembreRepository;
use App\Repository\DossierNatureListeRepository;
use App\Service\Commission\CommissionMembreCourrierService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("gestion_commissions")
 */
class CommissionMembresController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var CommissionMembreRepository */
    private $membreRepository;

    /** @var DossierNatureListeRepository */
    private $dossierNatureListeRepository;

    /** @var CommissionMembreCourrierService */
    private $courrierService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommissionMembreRepository $membreRepository,
        DossierNatureListeRepository $dossierNatureListeRepository,
        CommissionMembreCourrierService $courrierService
    ) {
        $this->entityManager = $entityManager;
        $this->membreRepository = $membreRepository;
        $this->dossierNatureListeRepository = $dossierNatureListeRepository;
        $this->courrierService = $courrierService;
    }

    /**
     * Onglet Membres — gestion des membres d'une commission.
     *
     * @Route("/admin/commissions/{id}/membres", name="admin_commissions_membres", requirements={"id"="\d+"}, methods={"GET", "POST"})
     */
    public function membres(Commission $commission, Request $request): Response
    {
        // Pré-chargement des membres avec leurs relations pour éviter N+1
        $this->membreRepository->findByCommissionWithRelations($commission);

        // Capture les membres existants avant la soumission du formulaire
        // pour identifier ceux qui seront supprimés et nettoyer leurs fichiers ODT.
        $originalMembres = $commission->getMembres()->toArray();

        $form = $this->createForm(CommissionMembresCollectionType::class, $commission, [
            'commission' => $commission,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Identifie les membres supprimés (présents avant soumission, absents après)
            $submittedMembreIds = [];
            foreach ($commission->getMembres() as $membre) {
                if ($membre->getId()) {
                    $submittedMembreIds[] = $membre->getId();
                }
            }

            $removedMembres = array_filter(
                $originalMembres,
                static function ($m) use ($submittedMembreIds): bool {
                    return $m->getId() && !\in_array($m->getId(), $submittedMembreIds, true);
                }
            );

            // Persistance : orphanRemoval=true supprime les membres retirés de la collection
            $this->entityManager->flush();

            // Suppression des fichiers ODT après persistance réussie
            foreach ($removedMembres as $membre) {
                $this->courrierService->deleteAllFiles($membre);
            }

            $this->addFlash('success', 'Les membres ont été sauvegardés avec succès.');

            return $this->redirectToRoute('admin_commissions_membres', ['id' => $commission->getId()]);
        }

        return $this->render('commissions/edit.html.twig', [
            'commission' => $commission,
            'active_tab' => 'membres',
            'form' => $form->createView(),
            'natures_by_type' => $this->dossierNatureListeRepository->findNaturesGroupedByType(),
        ]);
    }
}
