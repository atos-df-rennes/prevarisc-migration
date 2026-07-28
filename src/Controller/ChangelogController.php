<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\UtilisateurPreferences;
use App\Repository\UtilisateurPreferencesRepository;
use App\Service\VersionService;
use Doctrine\ORM\EntityManagerInterface;
use Michelf\Markdown;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChangelogController extends AbstractController
{
    public function __construct(private readonly VersionService $versionService, private readonly UtilisateurPreferencesRepository $prefsRepository)
    {
    }

    #[Route(path: '/changelog', name: 'changelog_index')]
    public function index(): Response
    {
        $rawSections = $this->versionService->getChangelogByMinorVersion();

        $sections = [];
        foreach ($rawSections as $minorVersion => $entries) {
            $rendered = [];
            foreach ($entries as $entry) {
                $rendered[] = [
                    'version' => $entry['version'],
                    'date' => $entry['date'],
                    'html' => Markdown::defaultTransform($entry['content']),
                ];
            }

            $sections[(string) $minorVersion] = $rendered;
        }

        return $this->render('changelog/index.html.twig', [
            'sections' => $sections,
        ]);
    }

    #[Route(path: '/changelog/banner', name: 'changelog_banner')]
    public function banner(): Response
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (null === $user) {
            return new Response('');
        }

        $currentVersion = $this->versionService->getCurrentVersion();

        try {
            $prefs = $this->prefsRepository->findOneBy(['utilisateur' => $user]);
            $derniereVersionVue = null !== $prefs ? $prefs->getDerniereVersionVue() : null;
        } catch (\Exception) {
            // La migration ajoutant DERNIERE_VERSION_VUE n'a pas encore été jouée.
            return new Response('');
        }

        return $this->render('changelog/banner.html.twig', [
            'show' => null !== $currentVersion && $currentVersion !== $derniereVersionVue,
            'currentVersion' => $currentVersion,
        ]);
    }

    #[Route(path: '/api/changelog/dismiss', name: 'changelog_dismiss', methods: ['POST'])]
    public function dismiss(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();

        if (null === $user) {
            return new JsonResponse(['success' => false], Response::HTTP_UNAUTHORIZED);
        }

        $csrfToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('changelog_dismiss', is_string($csrfToken) ? $csrfToken : null)) {
            return new JsonResponse(['success' => false], Response::HTTP_FORBIDDEN);
        }

        $prefs = $this->prefsRepository->findOneBy(['utilisateur' => $user]);

        if (null === $prefs) {
            $prefs = new UtilisateurPreferences();
            $prefs->setUtilisateur($user);
            $em->persist($prefs);
        }

        $prefs->setDerniereVersionVue($this->versionService->getCurrentVersion());
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
