<?php

namespace App\Controller\Admin;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Groupe;
use App\Entity\Privileges;
use App\Entity\Resources;
use App\Form\GroupeType;
use App\Form\MatriceDesDroitsType;
use App\Form\RessourceSpecialiseeDossierType;
use App\Form\RessourceSpecialiseeEtablissementType;
use App\Repository\GroupeRepository;
use App\Repository\ResourcesRepository;
use App\Repository\UtilisateurRepository;
use App\Security\Voter\DossierVoter;
use App\Security\Voter\Etablissement\EtablissementVoter;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesDossierNameGenerator;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesDossierTextGenerator;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementNameGenerator;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Route(requirements: ['id' => '\d+'])]
#[IsGranted('admin')]
class GroupeController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly GroupeRepository $groupeRepository, private readonly UtilisateurRepository $utilisateurRepository, private readonly ResourcesRepository $resourcesRepository, private readonly RessourcesSpecialiseesDossierTextGenerator $ressourcesSpecialiseesDossierTextGenerator, private readonly RessourcesSpecialiseesEtablissementTextGenerator $ressourcesSpecialiseesEtablissementTextGenerator)
    {
    }

    #[Route(path: '/admin/groupes/{id}/utilisateurs', name: 'admin_groupe_index')]
    public function index(Groupe $groupe): Response
    {
        $groupeId = $groupe->getId();

        return $this->render('utilisateurs/index.html.twig', [
            'utilisateurs_actifs' => $this->utilisateurRepository->findActives($groupeId),
            'utilisateurs_inactifs' => $this->utilisateurRepository->findInactives($groupeId),
            'groupes' => $this->groupeRepository->findAll(),
            'groupe_id' => $groupeId,
        ]);
    }

    #[Route(path: '/admin/groupes/ajouter', name: 'admin_groupe_add')]
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
            'form' => $form,
            'errors' => $form->getErrors(),
        ]);
    }

    #[Route(path: '/admin/groupes/{id}/modifier', name: 'admin_groupe_edit')]
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
            'form' => $form,
            'errors' => $form->getErrors(),
            'groupe_id' => $groupe->getId(),
        ]);
    }

    #[Route(path: '/admin/groupes/{id}/supprimer', name: 'admin_groupe_delete')]
    public function supprimer(Groupe $groupe): Response
    {
        $groupeId = $groupe->getId();

        // On ne supprime pas le groupe par défaut
        if (1 === $groupeId) {
            return $this->redirectToRoute('admin_utilisateurs_index');
        }

        $utilisateurs = $this->utilisateurRepository->findBy(['groupe' => $groupeId]);
        $groupeParDefaut = $this->groupeRepository->find(1);

        foreach ($utilisateurs as $utilisateur) {
            $utilisateur->setGroupe($groupeParDefaut);
        }

        $this->entityManager->flush();

        $this->entityManager->remove($groupe);
        $this->entityManager->flush();

        $this->addFlash('success', 'Le groupe a bien été supprimé.');

        return $this->redirectToRoute('admin_utilisateurs_index');
    }

    #[Route(path: '/admin/groupes/matrice-des-droits', name: 'admin_groupe_manage_privileges')]
    public function matriceDesDroits(Request $request): Response
    {
        $groupes = $this->groupeRepository->findAll();

        $form = $this->createForm(MatriceDesDroitsType::class, ['groupes' => $groupes]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'La matrice des droits a bien été mise à jour.');

            return $this->redirectToRoute('admin_groupe_manage_privileges');
        }

        return $this->render('groupe/matrice_droits.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/admin/groupes/ressources-specialisees', name: 'admin_groupe_ressources_specialisees')]
    public function ressourcesSpecialisees(): Response
    {
        $ressourceDossierForm = $this->createForm(RessourceSpecialiseeDossierType::class, null, [
            'action' => $this->generateUrl('admin_groupe_ressources_specialisees_add_dossier'),
        ]);
        $ressourceEtablissementForm = $this->createForm(RessourceSpecialiseeEtablissementType::class, null, [
            'action' => $this->generateUrl('admin_groupe_ressources_specialisees_add_etablissement'),
        ]);

        return $this->render('groupe/ressources_specialisees/index.html.twig', [
            'ressources_dossier' => $this->resourcesRepository->findRessourcesSpecialiseesDossier(),
            'ressources_etablissement' => $this->resourcesRepository->findRessourcesSpecialiseesEtablissement(),
            'form_dossier' => $ressourceDossierForm,
            'form_etablissement' => $ressourceEtablissementForm,
        ]);
    }

    #[Route(path: '/admin/groupes/ressources-specialisees/{id}/supprimer', name: 'admin_groupe_ressources_specialisees_delete', methods: ['DELETE'])]
    public function supprimerRessourceSpecialisee(Resources $resources): RedirectResponse
    {
        $this->entityManager->remove($resources);
        $this->entityManager->flush();

        return $this->redirectToRoute('admin_groupe_ressources_specialisees');
    }

    #[Route(path: '/admin/groupes/ressources-specialisees/dossier/ajouter', name: 'admin_groupe_ressources_specialisees_add_dossier', methods: ['POST'])]
    public function ajouterRessourceSpecialiseeDossier(Request $request): RedirectResponse
    {
        $resource = new Resources();

        $name = RessourcesSpecialiseesDossierNameGenerator::fromRequest($request);
        $text = $this->ressourcesSpecialiseesDossierTextGenerator->fromRequest($request);

        $resource->setName($name);
        $resource->setText($text);

        $this->entityManager->persist($resource);
        $this->entityManager->flush();

        $privilegeLecture = new Privileges();
        $privilegeLecture->setName(DossierVoter::LECTURE);
        $privilegeLecture->setText('Lecture');
        $privilegeLecture->setResource($resource);

        $privilegeModification = new Privileges();
        $privilegeModification->setName(DossierVoter::MODIFICATION);
        $privilegeModification->setText('Modifier');
        $privilegeModification->setResource($resource);

        $privilegeVerrouillage = new Privileges();
        $privilegeVerrouillage->setName(DossierVoter::VERROUILLAGE);
        $privilegeVerrouillage->setText("Verrouillage d'un dossier");
        $privilegeVerrouillage->setResource($resource);

        $this->entityManager->persist($privilegeLecture);
        $this->entityManager->persist($privilegeModification);
        $this->entityManager->persist($privilegeVerrouillage);
        $this->entityManager->flush();

        $this->addFlash('success', 'La ressource spécialisée a bien été ajoutée.');

        return $this->redirectToRoute('admin_groupe_ressources_specialisees');
    }

    #[Route(path: '/admin/groupes/ressources-specialisees/etablissement/ajouter', name: 'admin_groupe_ressources_specialisees_add_etablissement', methods: ['POST'])]
    public function ajouterRessourceSpecialiseeEtablissement(Request $request): RedirectResponse
    {
        $resource = new Resources();

        $name = RessourcesSpecialiseesEtablissementNameGenerator::fromRequest($request);
        $text = $this->ressourcesSpecialiseesEtablissementTextGenerator->fromRequest($request);

        $resource->setName($name);
        $resource->setText($text);

        $this->entityManager->persist($resource);
        $this->entityManager->flush();

        $privilegeLecture = new Privileges();
        $privilegeLecture->setName(EtablissementVoter::LECTURE);
        $privilegeLecture->setText('Lecture');
        $privilegeLecture->setResource($resource);

        $privilegeModification = new Privileges();
        $privilegeModification->setName(EtablissementVoter::MODIFICATION);
        $privilegeModification->setText('Modifier');
        $privilegeModification->setResource($resource);

        $this->entityManager->persist($privilegeLecture);
        $this->entityManager->persist($privilegeModification);
        $this->entityManager->flush();

        $this->addFlash('success', 'La ressource spécialisée a bien été ajoutée.');

        return $this->redirectToRoute('admin_groupe_ressources_specialisees');
    }
}
