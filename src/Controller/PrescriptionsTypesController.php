<?php

namespace App\Controller;

use App\Entity\PrescriptionArticleListe;
use App\Form\PrescriptionArticleListeType;
use App\Form\ReplacePrescriptionArticleListeType;
use App\Repository\PrescriptionArticleListeRepository;
use App\Service\Prescriptions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("admin")
 */
class PrescriptionsTypesController extends AbstractController
{
    /**
     * @Route("/admin/prescriptions", name="admin_prescriptions")
     */
    public function index(Prescriptions $servicePresc): Response
    {
        $listeArticles = $servicePresc->getArticlesListe();

        return $this->render('prescriptions_types/index.html.twig', [
            'liste_articles' => $listeArticles,
        ]);
    }
}
