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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("gestion_formulaires")
 */
class RubriqueController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/admin/formulaires/{formulaire}/rubriques/{rubrique}/modifier", name="admin_formulaires_edit_rubrique", methods={"GET", "POST"})
     *
     * @ParamConverter("formulaire", options={"id" = "formulaire"})
     * @ParamConverter("rubrique", options={"id" = "rubrique"})
     */
    public function edit(
        Request $request,
        CapsuleRubrique $formulaire,
        Rubrique $rubrique,
        RubriqueRepository $rubriqueRepository,
        ChampValeurListeRepository $champValeurListeRepository
    ): Response {
        $rubrique = $rubriqueRepository->findAllInformations($rubrique);
        $formRubrique = $this->createForm(RubriqueType::class, $rubrique);

        $champ = new Champ();
        $champ->setIdx($rubrique->getChamps()->count() + 1);
        $champ->setRubrique($rubrique);

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
            $this->entityManager->persist($champ);
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
            'form_rubrique' => $formRubrique->createView(),
            'form_champ' => $formChamp->createView(),
            'champ_valeurs_liste' => $champValeurListeRepository->findAll(),
        ]);
    }

    /**
     * @Route("/admin/formulaires/rubriques/{id}/supprimer", name="admin_formulaires_delete_rubrique", methods={"DELETE"})
     */
    public function delete(Rubrique $rubrique): JsonResponse
    {
        $this->entityManager->remove($rubrique);
        $this->entityManager->flush();

        return $this->json([]);
    }

    /**
     * @Route("/admin/formulaires/rubriques/{id}/index/{idx}", name="admin_formulaires_index_rubrique")
     */
    public function index(Rubrique $rubrique, int $idx): JsonResponse
    {
        $rubrique->setIdx($idx);
        $this->entityManager->flush();

        return $this->json([]);
    }
}
