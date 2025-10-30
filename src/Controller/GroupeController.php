<?php

namespace App\Controller;

use App\Entity\Groupe;
use App\Entity\Resources;
use App\Form\GroupeType;
use App\Form\MatriceDesDroitsType;
use App\Form\RessourceSpecialiseeDossierType;
use App\Form\RessourceSpecialiseeEtablissementType;
use App\Repository\GroupeRepository;
use App\Repository\ResourcesRepository;
use App\Repository\UtilisateurRepository;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesDossierNameGenerator;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesDossierTextGenerator;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementNameGenerator;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
    public function ressourcesSpecialisees(ResourcesRepository $resourcesRepository): Response
    {
        $ressourceDossierForm = $this->createForm(RessourceSpecialiseeDossierType::class, null, [
            'action' => $this->generateUrl('admin_groupe_ressources_specialisees_add_dossier'),
        ]);
        $ressourceEtablissementForm = $this->createForm(RessourceSpecialiseeEtablissementType::class, null, [
            'action' => $this->generateUrl('admin_groupe_ressources_specialisees_add_etablissement'),
        ]);

        return $this->render('groupe/ressources_specialisees/index.html.twig', [
            'ressources_dossier' => $resourcesRepository->findRessourcesSpecialiseesDossier(),
            'ressources_etablissement' => $resourcesRepository->findRessourcesSpecialiseesEtablissement(),
            'form_dossier' => $ressourceDossierForm->createView(),
            'form_etablissement' => $ressourceEtablissementForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/groupes/ressources-specialisees/{id}/supprimer", name="admin_groupe_ressources_specialisees_delete", methods={"DELETE"})
     */
    public function supprimerRessourceSpecialisee(Resources $resources): RedirectResponse
    {
        $this->entityManager->remove($resources);
        $this->entityManager->flush();

        return $this->redirectToRoute('admin_groupe_ressources_specialisees');
    }

    /**
     * @Route("/admin/groupes/ressources-specialisees/dossier/ajouter", name="admin_groupe_ressources_specialisees_add_dossier", methods={"POST"}))
     */
    public function ajouterRessourceSpecialiseeDossier(Request $request, RessourcesSpecialiseesDossierTextGenerator $ressourcesSpecialiseesDossierTextGenerator): RedirectResponse
    {
        $resource = new Resources();

        $name = RessourcesSpecialiseesDossierNameGenerator::fromRequest($request);
        $text = $ressourcesSpecialiseesDossierTextGenerator->fromRequest($request);

        $resource->setName($name);
        $resource->setText($text);

        $this->entityManager->persist($resource);
        $this->entityManager->flush();

        $this->addFlash('success', 'La ressource spécialisée a bien été ajoutée.');

        return $this->redirectToRoute('admin_groupe_ressources_specialisees');
    }

    /**
     * @Route("/admin/groupes/ressources-specialisees/etablissement/ajouter", name="admin_groupe_ressources_specialisees_add_etablissement", methods={"POST"}))
     */
    public function ajouterRessourceSpecialiseeEtablissement(Request $request, RessourcesSpecialiseesEtablissementTextGenerator $ressourcesSpecialiseesEtablissementTextGenerator): RedirectResponse
    {
        $resource = new Resources();

        $name = RessourcesSpecialiseesEtablissementNameGenerator::fromRequest($request);
        $text = $ressourcesSpecialiseesEtablissementTextGenerator->fromRequest($request);

        dd($name, $text);

        $resource->setName($name);
        $resource->setText($text);

        $this->entityManager->persist($resource);
        $this->entityManager->flush();

        $this->addFlash('success', 'La ressource spécialisée a bien été ajoutée.');

        return $this->redirectToRoute('admin_groupe_ressources_specialisees');
    }
}
