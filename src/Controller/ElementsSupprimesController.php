<?php

namespace App\Controller;

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
     * @Route("/admin/elements-supprimes", name="admin_elements_supprimes")
     */
    public function index(EtablissementRepository $etablissementRepository): Response
    {
        $etablissementsSupprimes = $etablissementRepository->findDeleted();

        return $this->render('elements_supprimes/index.html.twig', [
            'etablissements_supprimes' => $etablissementsSupprimes,
        ]);
    }
}
