<?php

namespace App\Controller;

use App\Entity\CapsuleRubrique;
use App\Entity\Champ;
use App\Entity\Rubrique;
use App\Form\ChampType;
use App\Form\RubriqueType;
use App\Repository\ChampValeurListeRepository;
use App\Repository\RubriqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('gestion_formulaires')]
class RubriqueController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly RubriqueRepository $rubriqueRepository, private readonly ChampValeurListeRepository $champValeurListeRepository)
    {
    }

    #[Route(path: '/admin/formulaires/{formulaire}/rubriques/{rubrique}/modifier', name: 'admin_formulaires_edit_rubrique', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(id: 'formulaire')] CapsuleRubrique $formulaire,
        #[MapEntity(id: 'rubrique')] Rubrique $rubrique
    ): Response {
        $rubrique = $this->rubriqueRepository->findAllInformations($rubrique);
        $formRubrique = $this->createForm(RubriqueType::class, $rubrique);

        $champ = new Champ();

        $formChamp = $this->createForm(ChampType::class, $champ);

        $formRubrique->handleRequest($request);
        if ($formRubrique->isSubmitted() && $formRubrique->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'La rubrique a bien été modifiée.');

            return $this->redirectToRoute('admin_formulaires_edit_rubrique', [
                'formulaire' => $formulaire->getId(),
                'rubrique' => $rubrique->getId(),
            ]);
        }

        $formChamp->handleRequest($request);
        if ($formChamp->isSubmitted() && $formChamp->isValid()) {
            $rubrique->addChamp($champ);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le champ a bien été ajouté.');

            return $this->redirectToRoute('admin_formulaires_edit_rubrique', [
                'formulaire' => $formulaire->getId(),
                'rubrique' => $rubrique->getId(),
            ]);
        }

        return $this->render('formulaires/rubrique/modification.html.twig', [
            'formulaire' => $formulaire,
            'rubrique' => $rubrique,
            'form_rubrique' => $formRubrique,
            'form_champ' => $formChamp,
            'champ_valeurs_liste' => $this->champValeurListeRepository->findAll(),
        ]);
    }

    #[Route(path: '/admin/formulaires/rubriques/{id}/supprimer', name: 'admin_formulaires_delete_rubrique', methods: ['DELETE'])]
    public function delete(Rubrique $rubrique): JsonResponse
    {
        $this->entityManager->remove($rubrique);
        $this->entityManager->flush();

        return $this->json([]);
    }

    #[Route(path: '/admin/formulaires/rubriques/{id}/index/{idx}', name: 'admin_formulaires_index_rubrique')]
    public function index(Rubrique $rubrique, int $idx): JsonResponse
    {
        $rubrique->setIdx($idx);
        $this->entityManager->flush();

        return $this->json([]);
    }
}
