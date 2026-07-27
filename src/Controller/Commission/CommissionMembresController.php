<?php

namespace App\Controller\Commission;

use App\Entity\Commission;
use App\Entity\CommissionMembre;
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

#[IsGranted('gestion_commissions')]
class CommissionMembresController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager, private CommissionMembreRepository $membreRepository, private DossierNatureListeRepository $dossierNatureListeRepository, private CommissionMembreCourrierService $courrierService)
    {
    }

    /**
     * Onglet Membres — gestion des membres d'une commission.
     */
    #[Route(path: '/admin/commissions/{id}/membres', name: 'admin_commissions_membres', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function membres(Commission $commission, Request $request): Response
    {
        // Pré-chargement des membres avec leurs relations pour éviter N+1
        $this->membreRepository->findByCommissionWithRelations($commission);

        // Capture les membres existants avant la soumission du formulaire
        // pour identifier ceux qui seront supprimés et nettoyer leurs fichiers ODT.
        // Note: toArray() crée une COPIE (pas une référence), sinon handleRequest()
        // modifierait aussi $originalMembres.
        $originalMembres = $commission->getMembres()->toArray();

        $form = $this->createForm(CommissionMembresCollectionType::class, $commission, [
            'commission' => $commission,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Identifie les membres supprimés : présents avant, absents après soumission
            $removedMembres = array_filter(
                $originalMembres,
                static fn (CommissionMembre $membre): bool => !$commission->getMembres()->contains($membre)
            );

            // Capture les IDs AVANT le flush (après, ils seront null)
            $removedMembresWithIds = [];
            foreach ($removedMembres as $membre) {
                $id = $membre->getId();
                if ($id) {
                    $removedMembresWithIds[$id] = $membre;
                }
            }

            // Persistance : orphanRemoval=true supprime les membres retirés de la collection
            $this->entityManager->flush();

            // Suppression des fichiers ODT seulement après flush réussi
            // Ainsi, si le flush échoue, les fichiers ne sont pas supprimés
            foreach ($removedMembresWithIds as $id => $membre) {
                $this->courrierService->deleteAllFiles($membre, $id);
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
