<?php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use App\Entity\UtilisateurPreferences;
use App\Form\UtilisateurType;
use App\Repository\GroupeRepository;
use App\Repository\UtilisateurRepository;
use App\Service\Avatar;
use App\Service\Image;
use App\Service\UtilisateurManager;
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
    public function __construct(private EntityManagerInterface $entityManager, private UtilisateurRepository $utilisateurRepository, private GroupeRepository $groupeRepository, private Avatar $avatar, private Image $image, private UtilisateurManager $utilisateurManager)
    {
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
            // Délégué à UtilisateurManager pour centraliser la logique.
            $this->utilisateurManager->processPasswordBeforePersist($utilisateur);

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
            // Délégué à UtilisateurManager pour centraliser la logique du password.
            $this->utilisateurManager->processPasswordBeforePersist($utilisateur);

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
