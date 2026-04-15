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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    public function __construct(
        EtablissementRepository $etablissementRepository,
        DossierRepository $dossierRepository,
        EtablissementService $etablissementService,
        DossierService $dossierService,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->etablissementRepository = $etablissementRepository;
        $this->dossierRepository = $dossierRepository;
        $this->etablissementService = $etablissementService;
        $this->dossierService = $dossierService;
        $this->urlGenerator = $urlGenerator;
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

        // @todo Décommissionnement legacy : remplacer par return $this->redirect($request->headers->get('referer'));
        // Cette redirection via le legacy ne sert qu'à vider le cacheSearch Zend (inutile une fois le legacy retiré).
        return $this->redirect('/legacy/admin/clear-search-cache-and-redirect?target='.$request->headers->get('referer'));
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

        // @todo Décommissionnement legacy : remplacer par return $this->redirectToRoute('admin_elements_supprimes');
        // Cette redirection via le legacy ne sert qu'à vider le cacheSearch Zend (inutile une fois le legacy retiré).
        $target = $this->urlGenerator->generate('admin_elements_supprimes');

        return $this->redirect('/legacy/admin/clear-search-cache-and-redirect?target='.$target);
    }
}
