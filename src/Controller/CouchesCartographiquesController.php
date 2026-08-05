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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[IsGranted('admin')]
class CouchesCartographiquesController extends AbstractController
{
    public function __construct(private readonly CouchesCarto $couchesCarto, private readonly SerializerInterface $serializer, private readonly LoggerInterface $logger, private readonly CoucheCartoRepository $coucheCartoRepository, private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route(path: '/admin/couches-cartographiques', name: 'couches_cartographiques')]
    public function index(): Response
    {
        /** @var string $autoconfPath */
        $autoconfPath = $this->getParameter('app.carto_autoconf_path');

        return $this->render('couches_cartographiques/index.html.twig', [
            'key_ign' => $this->getParameter('app.ign_key'),
            'geoconcept_url' => $this->getParameter('app.geoconcept_url'),
            'key_googlemap' => $this->getParameter('app.google_map_key'),
            'default_lon' => $this->getParameter('app.carto_default_lon'),
            'default_lat' => $this->getParameter('app.carto_default_lat'),
            'autoconf_path' => file_exists($autoconfPath) ? $autoconfPath : '',
            'projection' => $this->getParameter('app.geoconcept_projection'),
            'token' => $this->getParameter('app.geoconcept_token'),
            'app_id' => $this->getParameter('app.geoconcept_app_id'),
            'geoconcept_layer' => $this->getParameter('app.geoconcept_layer'),
            'couches_cartographiques' => $this->coucheCartoRepository->findAllOrdered(),
            'couches_cartographiques_json_encoded' => $this->serializer->serialize($this->coucheCartoRepository->findAllOrdered(), 'json')
        ]);
    }

    #[Route(path: '/admin/couches-cartographiques/ajouter', name: 'couches_cartographiques_add')]
    public function ajouter(Request $request): Response
    {
        $coucheCarto = new CoucheCarto();

        $form = $this->createForm(CoucheCartoType::class, $coucheCarto);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($coucheCarto);
            $this->entityManager->flush();

            $this->addFlash('success', 'La couche cartographique a été ajoutée.');

            return $this->redirectToRoute('couches_cartographiques');
        }

        return $this->render('couches_cartographiques/ajout.html.twig', [
            'form' => $form,
            'errors' => $form->getErrors(),
        ]);
    }

    #[Route(path: '/admin/couches-cartographiques/ajouter-ign', name: 'couches_cartographiques_add_IGN')]
    public function ajouterIGN(Request $request): Response
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

            $this->entityManager->persist($coucheCarto);
            $this->entityManager->flush();
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

    #[Route(path: '/admin/couches-cartographiques/reordonner', name: 'couches_cartographiques_order')]
    public function changerOrdre(Request $request): Response
    {
        $couchesCarto = $this->coucheCartoRepository->findAllOrdered();

        $form = $this->createForm(ReOrderCoucheCartoType::class, ['couchesCarto' => $couchesCarto]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($couchesCarto as $coucheCarto) {
                $this->entityManager->persist($coucheCarto);
            }

            $this->entityManager->flush();

            return $this->redirectToRoute('couches_cartographiques');
        }

        return $this->render('couches_cartographiques/change_order.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/admin/couches-cartographiques/{id}/modifier', name: 'couche_cartographique_edit')]
    public function modifier(Request $request, CoucheCarto $coucheCarto): Response
    {
        $form = $this->createForm(CoucheCartoType::class, $coucheCarto);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'La couche cartographique a bien été modifiée.');

            return $this->redirectToRoute('couches_cartographiques');
        }

        return $this->render('couches_cartographiques/ajout.html.twig', [
            'form' => $form,
            'errors' => $form->getErrors(),
            'couche_id' => $coucheCarto->getId(),
        ]);
    }

    #[Route(path: '/admin/couches-cartographiques/{id}/supprimer', name: 'couche_cartographique_delete')]
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
