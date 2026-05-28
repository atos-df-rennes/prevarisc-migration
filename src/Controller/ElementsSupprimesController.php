<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;
use App\Service\Dossier as DossierService;
use App\Service\Etablissement as EtablissementService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

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

    /** @var TagAwareCacheInterface */
    private $searchCache;

    public function __construct(
        EtablissementRepository $etablissementRepository,
        DossierRepository $dossierRepository,
        EtablissementService $etablissementService,
        DossierService $dossierService,
        TagAwareCacheInterface $searchCache
    ) {
        $this->etablissementRepository = $etablissementRepository;
        $this->dossierRepository = $dossierRepository;
        $this->etablissementService = $etablissementService;
        $this->dossierService = $dossierService;
        $this->searchCache = $searchCache;
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
     * @Route("/admin/elements-supprimes/dossier/restaurer/{id}", name="admin_elements_supprimes_restore_dossier", requirements={"id"="\d+"})
     *
     * @IsGranted("delete_dossier")
     */
    public function restoreDossier(Request $request, Dossier $id): Response
    {
        $this->dossierService->restore($id);
        $this->addFlash('success', 'Le dossier a bien été rétabli.');

        $this->searchCache->invalidateTags(['search']);

        /** @var string $referer */
        $referer = $request->headers->get('referer', $this->generateUrl('admin_elements_supprimes'));

        return $this->redirect($referer);
    }

    /**
     * @Route("/admin/elements-supprimes/etablissement/restaurer/{id}", name="admin_elements_supprimes_restore_etablissement", requirements={"id"="\d+"})
     *
     * @IsGranted("delete_etablissement")
     */
    public function restoreEtablissement(Etablissement $id): Response
    {
        $this->etablissementService->restore($id);
        $this->addFlash('success', 'L\'établissement a bien été rétabli.');

        $this->searchCache->invalidateTags(['search']);

        return $this->redirectToRoute('admin_elements_supprimes');
    }
}
