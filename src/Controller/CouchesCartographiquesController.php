<?php

namespace App\Controller;

use App\Entity\CoucheCarto;
use App\Form\CoucheCartoType;
use App\Form\ReOrderCoucheCartoType;
use App\Repository\CoucheCartoRepository;
use App\Service\CouchesCarto;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class CouchesCartographiquesController extends AbstractController
{
    /** @var SerializerInterface */
    private $serializer;

    /** @var CouchesCarto */
    private $couchesCarto;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @var CoucheCartoRepository
     */
    private $coucheCartoRepository;

    public function __construct(CouchesCarto $couchesCarto, SerializerInterface $serializer, LoggerInterface $logger, CoucheCartoRepository $coucheCartoRepository)
    {
        $this->couchesCarto = $couchesCarto;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->coucheCartoRepository = $coucheCartoRepository;
    }

    /**
     * @Route("/admin/couches-cartographiques", name="couches_cartographiques")
     */
    public function index(): Response
    {
        /** @var string $documentRoot */
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        /** @var string $autoconfPath */
        $autoconfPath = $this->getParameter('app.carto_autoconf_path');

        return $this->render('couches_cartographiques/index.html.twig', [
            'key_ign' => $this->getParameter('app.ign_key'),
            'geoconcept_url' => $this->getParameter('app.geoconcept_url'),
            'key_googlemap' => $this->getParameter('app.google_map_key'),
            'default_lon' => $this->getParameter('app.carto_default_lon'),
            'default_lat' => $this->getParameter('app.carto_default_lat'),
            // @todo: Le chemin n'est pas correct, à modifier
            'autoconf_path' => file_exists($documentRoot.$autoconfPath),
            'projection' => $this->getParameter('app.geoconcept_projection'),
            'token' => $this->getParameter('app.geoconcept_token'),
            'app_id' => $this->getParameter('app.geoconcept_app_id'),
            'geoconcept_layer' => $this->getParameter('app.geoconcept_layer'),
            'couches_cartographiques' => $this->coucheCartoRepository->findAllOrdered(),
            'couches_cartographiques_json_encoded' => $this->serializer->serialize($this->coucheCartoRepository->findAllOrdered(), 'json')
        ]);
    }

    /**
     * @Route("/admin/couches-cartographiques/ajouter", name="couches_cartographiques_add")
     */
    public function ajouter(Request $request, EntityManagerInterface $entityManager): Response
    {
        $coucheCarto = new CoucheCarto();

        $form = $this->createForm(CoucheCartoType::class, $coucheCarto);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
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
     * @Route("/admin/couches-cartographiques/ajouter-ign", name="couches_cartographiques_add_IGN")
     */
    public function ajouterIGN(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $coucheCartoData = $request->request->all();

            /** @var string $coucheCartoNom */
            $coucheCartoNom = $coucheCartoData['nom'];
            /** @var string $coucheCartoToken */
            $coucheCartoToken = $coucheCartoData['token'];

            if (!$this->isCsrfTokenValid('ajouter-IGN-'.$coucheCartoNom, $coucheCartoToken)) {
                $this->logger->error(sprintf('Problème avec le token CSR pour la couche %s.', $coucheCartoNom));
                $this->addFlash('danger', sprintf("Un problème est survenu lors de l'ajout de la couche cartographique IGN %s.", $coucheCartoNom));

                return $this->redirectToRoute('couches_cartographiques');
            }

            $coucheCartoData['transparent'] = isset($coucheCartoData['transparent']);

            $coucheCarto = $this->serializer->deserialize(json_encode($coucheCartoData), CoucheCarto::class, 'json');

            $entityManager->persist($coucheCarto);
            $entityManager->flush();
            $this->addFlash('success', 'La couche cartographique IGN a été ajoutée.');

            return $this->redirectToRoute('couches_cartographiques');
        }

        $couchesIGN = $this->couchesCarto->getCouchesIGN();
        if (isset($couchesIGN['error'])) {
            $this->addFlash('danger', $couchesIGN['error']);

            return $this->redirectToRoute('couches_cartographiques');
        }

        return $this->render('couches_cartographiques/ajoutIGN.html.twig', [
            'couchesIGN' => $couchesIGN,
        ]);
    }

    /**
     * @Route("/admin/couches-cartographiques/reordonner", name="couches_cartographiques_order")
     */
    public function changerOrdre(Request $request, EntityManagerInterface $entityManager): Response
    {
        $couchesCarto = $this->coucheCartoRepository->findAllOrdered();

        $form = $this->createForm(ReOrderCoucheCartoType::class, ['couchesCarto' => $couchesCarto]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
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
     * @Route("/admin/couches-cartographiques/{id}/modifier", name="couche_cartographique_edit")
     */
    public function modifier(Request $request, CoucheCarto $coucheCarto, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CoucheCartoType::class, $coucheCarto);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
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
     * @Route("/admin/couches-cartographiques/{id}/supprimer", name="couche_cartographique_delete")
     */
    public function supprimer(CoucheCarto $coucheCarto): Response
    {
        try {
            $this->coucheCartoRepository->remove($coucheCarto);
            $this->addFlash('success', 'La couche cartographique a été supprimée.');
        } catch (\Exception $exception) {
            $this->addFlash('danger', 'La couche cartographique n\'a pas été supprimée. Veuillez rééssayez. ('.$exception->getMessage().')');
        }

        return $this->redirectToRoute('couches_cartographiques');
    }
}
