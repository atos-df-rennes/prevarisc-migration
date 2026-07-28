<?php

namespace App\Controller;

use App\Entity\TexteApplicable;
use App\Form\TexteApplicableType;
use App\Repository\TexteApplicableRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('gestion_textes_applicables')]
class TextesApplicablesController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly TexteApplicableRepository $texteApplicableRepository, private readonly \App\Service\TexteApplicable $serviceTexteApplicable)
    {
    }

    #[Route(path: '/admin/textes-applicables', name: 'admin_textes_applicables')]
    public function index(): Response
    {
        $textesApplicables = $this->texteApplicableRepository->findAllSorted();

        return $this->render('textes_applicables/index.html.twig', [
            'textesApplicables' => $textesApplicables,
        ]);
    }

    #[Route(path: '/admin/textes-applicables/add', name: 'admin_textes_applicables_add', options: ['expose' => true])]
    public function ajouter(Request $request): Response
    {
        $texteApplicable = new TexteApplicable();

        $form = $this->createForm(TexteApplicableType::class, $texteApplicable, [
            'action' => $this->generateUrl('admin_textes_applicables_add'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // @todo: gestion de l'affichage prescription
                // la valeur oui/non n'est pas conservée, peut-être le gérer mais quid des conséquences d'affichage/masquaque
                $prescription = (bool) $form->get('prescription')->getData();
                $this->serviceTexteApplicable->save($texteApplicable, $prescription);

                $this->addFlash('success', 'Texte applicable ajouté.');

                return $this->redirectToRoute('admin_textes_applicables');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de la sauvegarde du texte : '.$e->getMessage());
            }
        }

        return $this->render('textes_applicables/ajout.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/admin/textes-applicables/{id}/edit', name: 'admin_textes_applicables_edit', options: ['expose' => true])]
    public function modifier(Request $request, TexteApplicable $texteApplicable): Response
    {
        $form = $this->createForm(TexteApplicableType::class, $texteApplicable, [
            'action' => $this->generateUrl('admin_textes_applicables_edit', ['id' => $texteApplicable->getId()]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $prescription = (bool) $form->get('prescription')->getData();
                $this->serviceTexteApplicable->save($texteApplicable, $prescription);

                $this->addFlash('success', 'Texte applicable modifié.');

                return $this->redirectToRoute('admin_textes_applicables');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de la sauvegarde du texte : '.$e->getMessage());
            }
        }

        return $this->render('textes_applicables/ajout.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/admin/textes-applicables/updateorder', name: 'admin_textes_applicables_updateorder', options: ['expose' => true])]
    public function ordonner(Request $request): JsonResponse
    {
        try {
            $tableUpdate = $request->request->get('tableUpdate');

            if (!\is_string($tableUpdate)) {
                throw new \Exception('Le format de la table de mise à jour est incorrect.');
            }

            $taIds = explode(',', $tableUpdate);

            foreach ($taIds as $key => $id) {
                $texteApplicable = $this->texteApplicableRepository->find($id);

                if (null === $texteApplicable) {
                    continue;
                }

                $texteApplicable->setNum($key);
                $this->entityManager->persist($texteApplicable);
            }

            $this->entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => 'Ordre des textes applicables mis à jour.']);
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false, 'error' => 'Erreur lors de la mise à jour de l\'ordre : '.$exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
