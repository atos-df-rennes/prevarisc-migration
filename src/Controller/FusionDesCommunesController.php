<?php

namespace App\Controller;

use App\Dto\FusionDesCommunes\ResultatFusion;
use App\Exception\FusionTechniqueException;
use App\Form\FusionDesCommunesType;
use App\Service\FusionDesCommunes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("admin")
 */
class FusionDesCommunesController extends AbstractController
{
    /**
     * @var FusionDesCommunes
     */
    private $fusionDesCommunes;

    public function __construct(FusionDesCommunes $fusionDesCommunes)
    {
        $this->fusionDesCommunes = $fusionDesCommunes;
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
            } catch (FusionTechniqueException $exception) {
                $this->addFlash('danger', $exception->getMessage());

                return $this->redirectToRoute('admin_fusion_des_communes');
            }

            $this->ajouterFlashResultat($resultat);

            return $this->redirectToRoute('admin_fusion_des_communes');
        }

        return $this->render('fusion_des_communes/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Compose les messages flash à partir du résultat de fusion :
     * succès global, succès partiel avec avertissements par lot KO, ou
     * information si aucune fusion n'était à traiter.
     */
    private function ajouterFlashResultat(ResultatFusion $resultat): void
    {
        foreach ($resultat->echecs() as $echec) {
            $this->addFlash('warning', \sprintf(
                "La commune %s n'a pas pu être fusionnée : %s",
                htmlspecialchars($echec['nom'], \ENT_QUOTES),
                htmlspecialchars($echec['message'], \ENT_QUOTES)
            ));
        }

        if ($resultat->aDesSucces()) {
            $this->addFlash(
                'success',
                $resultat->aDesEchecs()
                    ? 'Certaines communes ont bien été fusionnées. Consultez les avertissements pour les communes non traitées.'
                    : 'Les communes ont bien été fusionnées.'
            );

            return;
        }

        if (!$resultat->aDesEchecs()) {
            $this->addFlash('info', 'Aucune commune à fusionner.');
        }
    }
}
