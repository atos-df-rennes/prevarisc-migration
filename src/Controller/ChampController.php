<?php

namespace App\Controller;

use App\Entity\CapsuleRubrique;
use App\Entity\Champ;
use App\Entity\Rubrique;
use App\Repository\ChampValeurListeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("gestion_formulaires")
 */
class ChampController extends AbstractController
{
    /**
     * @Route("/admin/formulaires/{formulaire}/rubriques/{rubrique}/champs/{champ}/supprimer", name="admin_formulaires_delete_champ", methods={"DELETE"})
     *
     * @ParamConverter("formulaire", options={"id" = "formulaire"})
     * @ParamConverter("rubrique", options={"id" = "rubrique"})
     * @ParamConverter("champ", options={"id" = "champ"})
     */
    public function delete(CapsuleRubrique $formulaire, Rubrique $rubrique, Champ $champ, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($champ);
        $entityManager->flush();

        return $this->json([]);
    }

    /**
     * @Route("/admin/formulaires/champs/{id}/index/{idx}", name="admin_formulaires_index_champ")
     */
    public function index(Champ $champ, int $idx, EntityManagerInterface $entityManager): JsonResponse
    {
        $champ->setIdx($idx);
        $entityManager->flush();

        return $this->json([]);
    }

    /**
     * @Route("/admin/formulaires/update-champ-valeur-liste-idx", name="admin_formulaires_order_champ_valeur_liste")
     */
    public function orderChampValeurListe(Request $request, ChampValeurListeRepository $champValeurListeRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $champValeurListeId = filter_var($request->request->get('ID'), FILTER_VALIDATE_INT);

        if (false === $champValeurListeId) {
            throw new \InvalidArgumentException("L'identifiant de la valeur de liste n'a pas pu être récupéré correctement.");
        }

        $champValeurListe = $champValeurListeRepository->find($champValeurListeId);

        if (null === $champValeurListe) {
            throw new NotFoundHttpException(\sprintf("La valeur de liste d'identifiant %d n'existe pas.", $champValeurListeId));
        }

        $idx = filter_var($request->request->get('idx'), FILTER_VALIDATE_INT);

        if (false === $idx) {
            throw new \InvalidArgumentException("L'index de la valeur de liste n'a pas pu être récupéré correctement.");
        }

        $champValeurListe->setIdx($idx);

        $entityManager->flush();

        return $this->json([]);
    }
}
