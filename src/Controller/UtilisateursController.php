<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\UtilisateurPreferences;
use App\Form\UtilisateurType;
use App\Repository\GroupeRepository;
use App\Repository\UtilisateurRepository;
use App\Service\Avatar;
use App\Service\Image;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(requirements={"id"="\d+"})
 *
 * @IsGranted("admin")
 */
class UtilisateursController extends AbstractController
{
    /**
     * @Route("/admin/utilisateurs/", name="admin_utilisateurs_index")
     */
    public function utilisateurs(UtilisateurRepository $utilisateurRepository, GroupeRepository $groupeRepository): Response
    {
        return $this->render('utilisateurs/index.html.twig', [
            'utilisateurs_actifs' => $utilisateurRepository->findActives(),
            'utilisateurs_inactifs' => $utilisateurRepository->findInactives(),
            'groupes' => $groupeRepository->findAll(),
            'groupe_id' => null,
        ]);
    }

    /**
     * @Route("/admin/utilisateurs/ajouter", name="admin_utilisateurs_add")
     */
    public function ajouter(Request $request, Avatar $avatar, Image $image): Response
    {
        $utilisateur = new Utilisateur();

        $form = $this->createForm(UtilisateurType::class, $utilisateur);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $preferences = new UtilisateurPreferences();
            $preferences->setUtilisateur($utilisateur);
            $utilisateur->setPreferences($preferences);
            // @fixme: Utiliser l'encodage de Symfony + ré-encoder les mots de passe existants
            $utilisateur->setPassword(md5($utilisateur->getUsername().$this->getParameter('app.security_salt').$utilisateur->getPlainPassword()));

            $entityManager->persist($preferences);
            $entityManager->persist($utilisateur);
            $entityManager->flush();

            $userId = $utilisateur->getId();

            if (null === $userId) {
                throw new \Exception("L'utilisateur n'a pas pu être créé correctement.");
            }

            $photo = $form->get('photo')->getData();

            if ($photo && $photo instanceof UploadedFile) {
                $image->resize($photo->getPathname(), $avatar::SMALL_WIDTH, $avatar::SMALL_WIDTH)
                    ->save($avatar->path($avatar::SMALL_DIRECTORY, $userId));
                $image->resize($photo->getPathname(), $avatar::MEDIUM_WIDTH)
                    ->save($avatar->path($avatar::MEDIUM_DIRECTORY, $userId));
                $image->resize($photo->getPathname(), $avatar::LARGE_WIDTH)
                    ->save($avatar->path($avatar::LARGE_DIRECTORY, $userId));
            }

            $this->addFlash('success', "L'utilisateur a bien été ajouté.");

            return $this->redirectToRoute('admin_utilisateurs_index');
        }

        return $this->render('utilisateurs/ajout.html.twig', [
            'form' => $form->createView(),
            'errors' => $form->getErrors(),
        ]);
    }

    /**
     * @Route("/admin/utilisateurs/{id}/modifier", name="admin_utilisateurs_edit")
     */
    public function modifier(Request $request, Avatar $avatar, Image $image, Utilisateur $utilisateur): Response
    {
        $form = $this->createForm(UtilisateurType::class, $utilisateur);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            // On ne re-set pas le password s'il n'a pas été modifié
            // Sinon, si l'utilisateur avait un mot de passe interne et qu'il passe maintenant par le SSO, on ne conserve pas l'ancienne valeur
            if (null !== $utilisateur->getPlainPassword()) {
                // @fixme: Utiliser l'encodage de Symfony + ré-encoder les mots de passe existants
                $utilisateur->setPassword(md5($utilisateur->getUsername().$this->getParameter('app.security_salt').$utilisateur->getPlainPassword()));
            } elseif (null !== $utilisateur->getPassword() && $utilisateur->getSso()) {
                $utilisateur->setPassword(null);
            }

            $entityManager->persist($utilisateur);
            $entityManager->flush();

            $userId = $utilisateur->getId();

            if (null === $userId) {
                throw new \Exception("L'utilisateur n'a pas pu être modifié correctement.");
            }

            $photo = $form->get('photo')->getData();

            if ($photo && $photo instanceof UploadedFile) {
                $image->resize($photo->getPathname(), $avatar::SMALL_WIDTH, $avatar::SMALL_WIDTH)
                    ->save($avatar->path($avatar::SMALL_DIRECTORY, $userId));
                $image->resize($photo->getPathname(), $avatar::MEDIUM_WIDTH)
                    ->save($avatar->path($avatar::MEDIUM_DIRECTORY, $userId));
                $image->resize($photo->getPathname(), $avatar::LARGE_WIDTH)
                    ->save($avatar->path($avatar::LARGE_DIRECTORY, $userId));
            }

            $this->addFlash('success', "L'utilisateur a bien été modifié.");

            return $this->redirectToRoute('admin_utilisateurs_index');
        }

        return $this->render('utilisateurs/modification.html.twig', [
            'form' => $form->createView(),
            'errors' => $form->getErrors(),
            'utilisateur' => $utilisateur,
            'can_unlock_ip' => $this->getParameter('app.enforce_security') && $utilisateur->getFailedLoginAttempts() >= 2,
        ]);
    }
}
