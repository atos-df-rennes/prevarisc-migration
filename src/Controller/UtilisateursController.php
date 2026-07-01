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
     * @var UtilisateurRepository
     */
    private $utilisateurRepository;

    /**
     * @var GroupeRepository
     */
    private $groupeRepository;

    /**
     * @var Avatar
     */
    private $avatar;

    /**
     * @var Image
     */
    private $image;

    public function __construct(UtilisateurRepository $utilisateurRepository, GroupeRepository $groupeRepository, Avatar $avatar, Image $image)
    {
        $this->utilisateurRepository = $utilisateurRepository;
        $this->groupeRepository = $groupeRepository;
        $this->avatar = $avatar;
        $this->image = $image;
    }

    /**
     * @Route("/admin/utilisateurs/", name="admin_utilisateurs_index")
     */
    public function utilisateurs(): Response
    {
        return $this->render('utilisateurs/index.html.twig', [
            'utilisateurs_actifs' => $this->utilisateurRepository->findActives(),
            'utilisateurs_inactifs' => $this->utilisateurRepository->findInactives(),
            'groupes' => $this->groupeRepository->findAll(),
            'groupe_id' => null,
        ]);
    }

    /**
     * @Route("/admin/utilisateurs/ajouter", name="admin_utilisateurs_add")
     */
    public function ajouter(Request $request): Response
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

            $dateDerniereVisite = new \DateTime();
            $utilisateur->setDerniereVisitePiecesDossier($dateDerniereVisite);
            $utilisateur->setDerniereVisiteDashboard($dateDerniereVisite);

            $entityManager->persist($preferences);
            $entityManager->persist($utilisateur);
            $entityManager->flush();

            $userId = $utilisateur->getId();

            if (null === $userId) {
                throw new \Exception("L'utilisateur n'a pas pu être créé correctement.");
            }

            $photo = $form->get('photo')->getData();

            if ($photo && $photo instanceof UploadedFile) {
                $this->image->resize($photo->getPathname(), $this->avatar::SMALL_WIDTH, $this->avatar::SMALL_WIDTH)
                    ->save($this->avatar->path($this->avatar::SMALL_DIRECTORY, $userId));
                $this->image->resize($photo->getPathname(), $this->avatar::MEDIUM_WIDTH)
                    ->save($this->avatar->path($this->avatar::MEDIUM_DIRECTORY, $userId));
                $this->image->resize($photo->getPathname(), $this->avatar::LARGE_WIDTH)
                    ->save($this->avatar->path($this->avatar::LARGE_DIRECTORY, $userId));
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
    public function modifier(Request $request, Utilisateur $utilisateur): Response
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
                $this->image->resize($photo->getPathname(), $this->avatar::SMALL_WIDTH, $this->avatar::SMALL_WIDTH)
                    ->save($this->avatar->path($this->avatar::SMALL_DIRECTORY, $userId));
                $this->image->resize($photo->getPathname(), $this->avatar::MEDIUM_WIDTH)
                    ->save($this->avatar->path($this->avatar::MEDIUM_DIRECTORY, $userId));
                $this->image->resize($photo->getPathname(), $this->avatar::LARGE_WIDTH)
                    ->save($this->avatar->path($this->avatar::LARGE_DIRECTORY, $userId));
            }

            $this->addFlash('success', "L'utilisateur a bien été modifié.");

            return $this->redirect('/legacy/admin/utilisateurs/sauvegarder?userId='.$userId);
        }

        return $this->render('utilisateurs/modification.html.twig', [
            'form' => $form->createView(),
            'errors' => $form->getErrors(),
            'utilisateur' => $utilisateur,
            'can_unlock_ip' => $this->getParameter('app.enforce_security') && $utilisateur->getFailedLoginAttempts() >= 2,
        ]);
    }
}
