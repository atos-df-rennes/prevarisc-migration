<?php

namespace App\Controller;

use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;
use App\Service\Dossier as DossierService;
use App\Service\Etablissement as EtablissementService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("is_granted('admin') and (is_granted('delete_dossier') or is_granted('delete_etablissement'))")
 */
class ElementsSupprimesController extends AbstractController
{
    /**
     * @var EtablissementRepository
     */
    private $etablissementRepository;

    /**
     * @var DossierRepository
     */
    private $dossierRepository;

    /**
     * @var EtablissementService
     */
    private $etablissementService;

    /**
     * @var DossierService
     */
    private $dossierService;

    public function __construct(
        EtablissementRepository $etablissementRepository,
        DossierRepository $dossierRepository,
        EtablissementService $etablissementService,
        DossierService $dossierService
    ) {
        $this->etablissementRepository = $etablissementRepository;
        $this->dossierRepository = $dossierRepository;
        $this->etablissementService = $etablissementService;
        $this->dossierService = $dossierService;
    }

    /**
     * @Route("/admin/elements-supprimes", name="admin_elements_supprimes")
     */
    public function index(): Response
    {
        $etablissementsSupprimes = $this->etablissementRepository->findDeleted();
        $dossiersSupprimes = $this->dossierRepository->findDeleted();

        return $this->render('elements_supprimes/index.html.twig', [
            'etablissements_supprimes' => $etablissementsSupprimes,
            'dossiers_supprimes' => $dossiersSupprimes,
        ]);
    }

    /**
     * @Route("/admin/elements-supprimes/restore-dossier/{id}", name="admin_elements_supprimes_restore_dossier", requirements={"id"="\d+"})
     *
     * @Security("is_granted('delete_dossier')")
     */
    public function restoreDossier(int $id): Response
    {
        try {
            $this->dossierService->restore($id);

            $this->addFlash('success', 'Le dossier a bien été rétabli.');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->addFlash('error', $invalidArgumentException->getMessage());

            return $this->redirectToRoute('admin_elements_supprimes');
        }

        // Redirection vers le LegacyController pour nettoyer le cache
        return $this->redirect('/legacy/clear-cache-and-redirect?target='.urlencode('/admin/elements-supprimes'));
    }

    /**
     * @Route("/admin/elements-supprimes/restore-etablissement/{id}", name="admin_elements_supprimes_restore_etablissement", requirements={"id"="\d+"})
     *
     * @Security("is_granted('delete_etablissement')")
     */
    public function restoreEtablissement(int $id): Response
    {
        try {
            $this->etablissementService->restore($id);

            $this->addFlash('success', 'L\'établissement a bien été rétabli.');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->addFlash('error', $invalidArgumentException->getMessage());

            return $this->redirectToRoute('admin_elements_supprimes');
        }

        // Redirection vers le LegacyController pour nettoyer le cache
        return $this->redirect('/legacy/clear-cache-and-redirect?target='.urlencode('/admin/elements-supprimes'));
    }
}
