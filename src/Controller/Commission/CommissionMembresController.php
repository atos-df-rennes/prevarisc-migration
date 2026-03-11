<?php

namespace App\Controller\Commission;

use App\Entity\Commission;
use App\Form\CommissionMembresCollectionType;
use App\Repository\CommissionMembreRepository;
use App\Repository\DossierNatureListeRepository;
use App\Repository\EtablissementTypeRepository;
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

    /** @var EtablissementTypeRepository */
    private $etablissementTypeRepository;

    /** @var DossierNatureListeRepository */
    private $dossierNatureListeRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommissionMembreRepository $membreRepository,
        EtablissementTypeRepository $etablissementTypeRepository,
        DossierNatureListeRepository $dossierNatureListeRepository
    ) {
        $this->entityManager = $entityManager;
        $this->membreRepository = $membreRepository;
        $this->etablissementTypeRepository = $etablissementTypeRepository;
        $this->dossierNatureListeRepository = $dossierNatureListeRepository;
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

        $activitesByType = $this->etablissementTypeRepository->findActivitesGroupedByType();

        $form = $this->createForm(CommissionMembresCollectionType::class, $commission, [
            'commission' => $commission,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Les membres ont été sauvegardés avec succès.');

            return $this->redirectToRoute('admin_commissions_membres', ['id' => $commission->getId()]);
        }

        return $this->render('commissions/edit.html.twig', [
            'commission' => $commission,
            'active_tab' => 'membres',
            'form' => $form->createView(),
            'activites_by_type' => $activitesByType,
            'natures_by_type' => $this->dossierNatureListeRepository->findNaturesGroupedByType(),
        ]);
    }
}
