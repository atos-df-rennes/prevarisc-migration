<?php

namespace App\Controller;

use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;
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

    public function __construct(EtablissementRepository $etablissementRepository, DossierRepository $dossierRepository)
    {
        $this->etablissementRepository = $etablissementRepository;
        $this->dossierRepository = $dossierRepository;
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
}
