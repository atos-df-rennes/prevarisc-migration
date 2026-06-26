<?php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use App\Entity\UtilisateurPreferences;
use App\Form\UtilisateurType;
use App\Repository\GroupeRepository;
use App\Repository\UtilisateurRepository;
use App\Security\Md5SaltedEncoder;
use App\Service\Avatar;
use App\Service\Image;
use Doctrine\ORM\EntityManagerInterface;
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
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var UtilisateurRepository */
    private $utilisateurRepository;

    /** @var GroupeRepository */
    private $groupeRepository;

    /** @var Avatar */
    private $avatar;

    /** @var Image */
    private $image;

    /** @var Md5SaltedEncoder */
    private $passwordEncoder;

    public function __construct(EntityManagerInterface $entityManager, UtilisateurRepository $utilisateurRepository, GroupeRepository $groupeRepository, Avatar $avatar, Image $image, Md5SaltedEncoder $passwordEncoder)
    {
        $this->entityManager = $entityManager;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->groupeRepository = $groupeRepository;
        $this->avatar = $avatar;
        $this->image = $image;
        $this->passwordEncoder = $passwordEncoder;
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
            $preferences = new UtilisateurPreferences();
            $preferences->setUtilisateur($utilisateur);
            $utilisateur->setPreferences($preferences);
            // Encode en MD5 (format legacy) pour garantir la compatibilité du fallback Zend.
            // À remplacer par UserPasswordEncoderInterface une fois le legacy retiré.
            // Si l'utilisateur est en SSO, ne pas enregistrer de mot de passe local.
            if ($utilisateur->getSso()) {
                $utilisateur->setPassword(null);
            } else {
                $plain = $utilisateur->getPlainPassword();
                if (null !== $plain && '' !== $plain) {
                    $utilisateur->setPassword($this->passwordEncoder->encodePassword($plain, $utilisateur->getSalt()));
                }
            }

            $dateDerniereVisite = new \DateTime();
            $utilisateur->setDerniereVisitePiecesDossier($dateDerniereVisite);
            $utilisateur->setDerniereVisiteDashboard($dateDerniereVisite);

            $this->entityManager->persist($preferences);
            $this->entityManager->persist($utilisateur);
            $this->entityManager->flush();

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
            // On ne re-set pas le password s'il n'a pas été modifié
            // Sinon, si l'utilisateur avait un mot de passe interne et qu'il passe maintenant par le SSO, on ne conserve pas l'ancienne valeur
            if (null !== $utilisateur->getPlainPassword()) {
                // Encode en MD5 (format legacy) pour garantir la compatibilité du fallback Zend.
                // À remplacer par UserPasswordEncoderInterface une fois le legacy retiré.
                $utilisateur->setPassword($this->passwordEncoder->encodePassword($utilisateur->getPlainPassword(), $utilisateur->getSalt()));
            } elseif (null !== $utilisateur->getPassword() && $utilisateur->getSso()) {
                $utilisateur->setPassword(null);
            }

            $this->entityManager->persist($utilisateur);
            $this->entityManager->flush();

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
