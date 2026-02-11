<?php

namespace App\Controller;

use App\Entity\CapsuleRubrique;
use App\Entity\Champ;
use App\Entity\Rubrique;
use App\Form\ChampType;
use App\Repository\ChampRepository;
use App\Repository\ChampValeurListeRepository;
use App\Repository\TypeChampRepository;
use App\Service\Formulaires\Fusion\ChampCreator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("gestion_formulaires")
 */
class ChampController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var ChampRepository
     */
    private $champRepository;

    /**
     * @var ChampValeurListeRepository
     */
    private $champValeurListeRepository;

    /**
     * @var TypeChampRepository
     */
    private $typeChampRepository;

    public function __construct(EntityManagerInterface $entityManager, FormFactoryInterface $formFactory, ChampRepository $champRepository, ChampValeurListeRepository $champValeurListeRepository, TypeChampRepository $typeChampRepository)
    {
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
        $this->champRepository = $champRepository;
        $this->champValeurListeRepository = $champValeurListeRepository;
        $this->typeChampRepository = $typeChampRepository;
    }

    /**
     * @Route("/admin/formulaires/{formulaire}/rubriques/{rubrique}/champs/{champ}/modifier", name="admin_formulaires_edit_champ", methods={"GET", "POST"})
     *
     * @ParamConverter("formulaire", options={"id" = "formulaire"})
     * @ParamConverter("rubrique", options={"id" = "rubrique"})
     * @ParamConverter("champ", options={"id" = "champ"})
     */
    public function edit(
        Request $request,
        CapsuleRubrique $formulaire,
        Rubrique $rubrique,
        Champ $champ
    ): Response {
        $champ = $this->champRepository->findAllInformations($champ);
        $formChamp = $this->createForm(ChampType::class, $champ, [
            'is_edit' => true,
        ]);
        /*
         * On a besoin d'avoir le type avant le handleRequest() car l'input est disabled.
         * Cela crée une erreur lors de la sauvegarde.
         */
        $initialType = $champ->getType();

        $formChamp->handleRequest($request);
        if ($formChamp->isSubmitted() && $formChamp->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Le champ a bien été sauvegardé.');

            return $this->redirectToRoute('admin_formulaires_edit_champ', [
                'formulaire' => $formulaire->getId(),
                'rubrique' => $rubrique->getId(),
                'champ' => $champ->getId(),
            ]);
        }

        if (null === $initialType) {
            throw new \InvalidArgumentException('Le type du champ est invalide.');
        }

        $formChampEnfant = null;
        if ('Parent' === $initialType->getType()) {
            $champEnfant = new Champ();

            $types = $this->typeChampRepository->findAllowedTypeForParent();
            $formChampEnfant = $this->formFactory->createNamed('champ_enfant', ChampType::class, $champEnfant, [
                'types' => $types,
            ]);

            $formChampEnfant->handleRequest($request);
            if ($formChampEnfant->isSubmitted() && $formChampEnfant->isValid()) {
                $champ->addEnfant($champEnfant);
                $this->entityManager->flush();

                $this->addFlash('success', 'Le champ a bien été ajouté.');

                return $this->redirectToRoute('admin_formulaires_edit_champ', [
                    'formulaire' => $formulaire->getId(),
                    'rubrique' => $rubrique->getId(),
                    'champ' => $champ->getId(),
                ]);
            }
        }

        return $this->render('formulaires/champ/modification.html.twig', [
            'formulaire' => $formulaire,
            'rubrique' => $rubrique,
            'champ' => $champ,
            'form_champ' => $formChamp->createView(),
            'valeur_champ_fusion' => ChampCreator::make($formulaire, $rubrique, $champ),
            'champ_valeurs_liste' => $this->champValeurListeRepository->findAll(),
            'form_champ_enfant' => $formChampEnfant ? $formChampEnfant->createView() : null
        ]);
    }

    /**
     * @Route("/admin/formulaires/{formulaire}/rubriques/{rubrique}/champs/{champ}/supprimer", name="admin_formulaires_delete_champ", methods={"DELETE"})
     *
     * @ParamConverter("formulaire", options={"id" = "formulaire"})
     * @ParamConverter("rubrique", options={"id" = "rubrique"})
     * @ParamConverter("champ", options={"id" = "champ"})
     */
    public function delete(CapsuleRubrique $formulaire, Rubrique $rubrique, Champ $champ): JsonResponse
    {
        $this->entityManager->remove($champ);
        $this->entityManager->flush();

        return $this->json([]);
    }

    /**
     * @Route("/admin/formulaires/champs/{id}/index/{idx}", name="admin_formulaires_index_champ")
     */
    public function index(Champ $champ, int $idx): JsonResponse
    {
        $champ->setIdx($idx);
        $this->entityManager->flush();

        return $this->json([]);
    }

    /**
     * @Route("/admin/formulaires/update-champ-valeur-liste-idx", name="admin_formulaires_order_champ_valeur_liste")
     */
    public function orderChampValeurListe(Request $request): JsonResponse
    {
        $champValeurListeId = filter_var($request->request->get('ID'), FILTER_VALIDATE_INT);

        if (false === $champValeurListeId) {
            throw new \InvalidArgumentException("L'identifiant de la valeur de liste n'a pas pu être récupéré correctement.");
        }

        $champValeurListe = $this->champValeurListeRepository->find($champValeurListeId);

        if (null === $champValeurListe) {
            throw new NotFoundHttpException(\sprintf("La valeur de liste d'identifiant %d n'existe pas.", $champValeurListeId));
        }

        $idx = filter_var($request->request->get('idx'), FILTER_VALIDATE_INT);

        if (false === $idx) {
            throw new \InvalidArgumentException("L'index de la valeur de liste n'a pas pu être récupéré correctement.");
        }

        $champValeurListe->setIdx($idx);

        $this->entityManager->flush();

        return $this->json([]);
    }
}
