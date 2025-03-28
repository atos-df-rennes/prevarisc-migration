<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Form\FusionDesCommunesType;
use App\Service\FusionDesCommunes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @IsGranted("admin")
 */
class FusionDesCommunesController extends AbstractController
{
    /**
     * @Route("/admin/fusion-des-communes", name="admin_fusion_des_communes")
     */
    public function index(Request $request, ValidatorInterface $validator, FusionDesCommunes $fusionDesCommunes): Response
    {
        $form = $this->createForm(FusionDesCommunesType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $fichier */
            $fichier = $form->get('fichier')->getData();

            try {
                $fusionDesCommunes->fusionner(file_get_contents($fichier));
            } catch (\Exception $exception) {
                $this->addFlash('danger', $exception->getMessage());

                return $this->redirectToRoute('admin_fusion_des_communes');
            }

            $this->addFlash('success', 'Les communes ont bien été fusionnées.');

            return $this->redirectToRoute('admin_fusion_des_communes');
        }

        return $this->render('fusion_des_communes/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
