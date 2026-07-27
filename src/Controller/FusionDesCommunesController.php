<?php

namespace App\Controller;

use App\Exception\FusionTechnicalException;
use App\Form\FusionDesCommunesType;
use App\Service\FusionDesCommunes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("communes")
 */
class FusionDesCommunesController extends AbstractController
{
    public function __construct(private FusionDesCommunes $fusionDesCommunes)
    {
    }

    /**
     * @Route("/admin/fusion-des-communes", name="admin_fusion_des_communes")
     */
    public function index(Request $request): Response
    {
        $form = $this->createForm(FusionDesCommunesType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $fichier */
            $fichier = $form->get('fichier')->getData();

            try {
                $resultat = $this->fusionDesCommunes->fusionner(file_get_contents($fichier));
            } catch (FusionTechnicalException $exception) {
                $this->addFlash('danger', $exception->getMessage());

                return $this->redirectToRoute('admin_fusion_des_communes');
            }

            foreach ($this->fusionDesCommunes->composerFlashsResultat($resultat) as $flash) {
                $this->addFlash($flash['type'], $flash['message']);
            }

            return $this->redirectToRoute('admin_fusion_des_communes');
        }

        return $this->render('fusion_des_communes/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
