<?php

namespace App\Controller;

use App\Entity\Groupe;
use App\Form\GroupeType;
use App\Form\MatriceDesDroitsType;
use App\Repository\GroupeRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(requirements={"id"="\d+"})
 *
 * @IsGranted("admin")
 */
class GroupeController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/admin/groupes/{id}/utilisateurs", name="admin_groupe_index")
     */
    public function index(Groupe $groupe, GroupeRepository $groupeRepository, UtilisateurRepository $utilisateurRepository): Response
    {
        $groupeId = $groupe->getId();

        return $this->render('utilisateurs/index.html.twig', [
            'utilisateurs_actifs' => $utilisateurRepository->findActives($groupeId),
            'utilisateurs_inactifs' => $utilisateurRepository->findInactives($groupeId),
            'groupes' => $groupeRepository->findAll(),
            'groupe_id' => $groupeId,
        ]);
    }

    /**
     * @Route("/admin/groupes/ajouter", name="admin_groupe_add")
     */
    public function ajouter(Request $request): Response
    {
        $groupe = new Groupe();

        $form = $this->createForm(GroupeType::class, $groupe);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($groupe);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le groupe a bien été ajouté.');

            return $this->redirectToRoute('admin_utilisateurs_index');
        }

        return $this->render('groupe/ajout.html.twig', [
            'form' => $form->createView(),
            'errors' => $form->getErrors(),
        ]);
    }

    /**
     * @Route("/admin/groupes/{id}/modifier", name="admin_groupe_edit")
     */
    public function modifier(Request $request, Groupe $groupe): Response
    {
        $form = $this->createForm(GroupeType::class, $groupe);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Le groupe a bien été modifié.');

            return $this->redirectToRoute('admin_utilisateurs_index');
        }

        return $this->render('groupe/modification.html.twig', [
            'form' => $form->createView(),
            'errors' => $form->getErrors(),
            'groupe_id' => $groupe->getId(),
        ]);
    }

    /**
     * @Route("/admin/groupes/{id}/supprimer", name="admin_groupe_delete")
     */
    public function supprimer(Groupe $groupe, UtilisateurRepository $utilisateurRepository, GroupeRepository $groupeRepository): Response
    {
        $groupeId = $groupe->getId();

        // On ne supprime pas le groupe par défaut
        if (1 === $groupeId) {
            return $this->redirectToRoute('admin_utilisateurs_index');
        }

        $utilisateurs = $utilisateurRepository->findBy(['groupe' => $groupeId]);
        $groupeParDefaut = $groupeRepository->find(1);

        foreach ($utilisateurs as $utilisateur) {
            $utilisateur->setGroupe($groupeParDefaut);
        }
        $this->entityManager->flush();

        $this->entityManager->remove($groupe);
        $this->entityManager->flush();

        $this->addFlash('success', 'Le groupe a bien été supprimé.');

        return $this->redirectToRoute('admin_utilisateurs_index');
    }

    /**
     * @Route("/admin/groupes/matrice-des-droits", name="admin_groupe_manage_privileges")
     */
    public function matriceDesDroits(Request $request, GroupeRepository $groupeRepository): Response
    {
        $groupes = $groupeRepository->findAll();

        $form = $this->createForm(MatriceDesDroitsType::class, ['groupes' => $groupes]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'La matrice des droits a bien été mise à jour.');

            return $this->redirect('/legacy/admin/groupes/matrice-des-droits');
        }

        return $this->render('groupe/matrice_droits.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/groupes/ressources-specialisees", name="admin_groupe_ressources_specialisees")
     */
    public function ressourcesSpecialisees(): Response
    {
        return $this->render('groupe/ressources_specialisees.html.twig');
    }
}
