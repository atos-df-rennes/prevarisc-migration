<?php

namespace App\Controller;

use App\Entity\CoucheCarto;
use App\Form\CoucheCartoType;
use App\Form\ReOrderCoucheCartoType;
use App\Repository\CoucheCartoRepository;
use App\Service\CouchesCarto;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class CouchesCartographiquesController extends AbstractController
{
    /** @var SerializerInterface  */
    private $serializer;

    /** @var CouchesCarto  */
    private $couchesCarto;

    public function __construct(CouchesCarto $couchesCarto, SerializerInterface $serializer)
    {
        $this->couchesCarto = $couchesCarto;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/admin/couches-cartographiques", name="couches_cartographiques")
     */
    public function index(CoucheCartoRepository $coucheCartoRepository): Response
    {
        return $this->render('couches_cartographiques/index.html.twig', [
            'key_ign' => $this->getParameter('app.ign_key'),
            'geoconcept_url' => $this->getParameter('app.geoconcept_url'),
            'key_googlemap' => $this->getParameter('app.google_map_key'),
            'default_lon' => $this->getParameter('app.carto_default_lon'),
            'default_lat' => $this->getParameter('app.carto_default_lat'),
            // @todo: Le chemin n'est pas correct, à modifier
            'autoconf_path' => file_exists($_SERVER['DOCUMENT_ROOT'].(getenv('PREVARISC_CARTO_AUTOCONF_PATH') ?: '')),
            'projection' => $this->getParameter('app.geoconcept_projection'),
            'token' => $this->getParameter('app.geoconcept_token'),
            'app_id' => $this->getParameter('app.geoconcept_app_id'),
            'geoconcept_layer' => $this->getParameter('app.geoconcept_layer'),
            'couches_cartographiques' => $coucheCartoRepository->findAllOrdered(),
        ]);
    }

    /**
     * @Route("/admin/couches-cartographiques/add", name="couches_cartographiques_add")
     */
    public function ajouter(Request $request): Response
    {
        $coucheCarto = new CoucheCarto();

        $form = $this->createForm(CoucheCartoType::class, $coucheCarto);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($coucheCarto);
            $entityManager->flush();

            $this->addFlash('success', 'La couche cartographique a été ajoutée.');

            return $this->redirectToRoute('couches_cartographiques');
        }

        return $this->render('couches_cartographiques/ajout.html.twig', [
            'form' => $form->createView(),
            'errors' => $form->getErrors(),
        ]);
    }

    /**
     * @Route("/admin/couches-cartographiques/addCoucheIGN", name="couches_cartographiques_add_IGN")
     */
    public function ajouterIGN(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $coucheCartoData = $request->request->all();
            if ($this->isCsrfTokenValid('ajouter-IGN-'.$coucheCartoData['nom'], $coucheCartoData['token'])) {
                if (!isset($coucheCartoData['isBaseLayer'])) {
                    $coucheCartoData['isBaseLayer'] = false;
                }

                $coucheCartoData['transparent'] = isset($coucheCartoData['transparent']);

                $coucheCarto = $this->serializer->deserialize(json_encode($coucheCartoData), CoucheCarto::class, 'json');

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($coucheCarto);
                $entityManager->flush();
                $this->addFlash('success', 'La couche cartographique IGN a été ajoutée.');
            } else {
                $this->addFlash('error', 'Probleme avec le token CSRF');
            }

            return $this->redirectToRoute('couches_cartographiques');
        }

        $couchesIGN = $this->couchesCarto->getCouchesIGN();
        if (isset($couchesIGN['error'])) {
            $this->addFlash('error', $couchesIGN['error']);

            return $this->redirectToRoute('couches_cartographiques');
        }

        return $this->render('couches_cartographiques/ajoutIGN.html.twig', [
            'couchesIGN' => $couchesIGN,
        ]);
    }

    /**
     * @Route("/admin/couches-cartographiques/order", name="couches_cartographiques_order")
     */
    public function changerOrdre(Request $request, CoucheCartoRepository $coucheCartoRepository): Response
    {
        $couchesCarto = $coucheCartoRepository->findAllOrdered();

        $form = $this->createForm(ReOrderCoucheCartoType::class, ['couchesCarto' => $couchesCarto]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            foreach ($couchesCarto as $coucheCarto) {
                $entityManager->persist($coucheCarto);
            }

            $entityManager->flush();

            return $this->redirectToRoute('couches_cartographiques');
        }

        return $this->render('couches_cartographiques/change_order.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/couches-cartographiques/{id}/edit", name="couche_cartographique_edit")
     */
    public function modifier(Request $request, CoucheCarto $coucheCarto): Response
    {
        $form = $this->createForm(CoucheCartoType::class, $coucheCarto);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            $this->addFlash('success', 'La couche cartographique a bien été modifiée.');

            return $this->redirectToRoute('couches_cartographiques');
        }

        return $this->render('couches_cartographiques/ajout.html.twig', [
            'form' => $form->createView(),
            'errors' => $form->getErrors(),
            'couche_id' => $coucheCarto->getId(),
        ]);
    }

    /**
     * @Route("/admin/couches-cartographiques/{id}/delete", name="couche_cartographique_delete")
     */
    public function supprimer(CoucheCarto $coucheCarto, CoucheCartoRepository $coucheCartoRepository): Response
    {
        try {
            $coucheCartoRepository->remove($coucheCarto);
            $this->addFlash('success', 'La couche cartographique a été supprimée.');
        } catch (Exception $exception) {
            $this->addFlash('danger', 'La couche cartographique n\'a pas été supprimée. Veuillez rééssayez. ('.$exception->getMessage().')');
        }

        return $this->redirectToRoute('couches_cartographiques');
    }
}
