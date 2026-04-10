<?php

namespace App\Controller;

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
    /** @var VersionService */
    private $versionService;

    public function __construct(VersionService $versionService)
    {
        $this->versionService = $versionService;
    }

    /**
     * @Route("/changelog", name="changelog_index")
     */
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

    /**
     * @Route("/changelog/banner", name="changelog_banner")
     */
    public function banner(UtilisateurPreferencesRepository $prefsRepository): Response
    {
        /** @var \App\Entity\Utilisateur|null $user */
        $user = $this->getUser();

        if (null === $user) {
            return new Response('');
        }

        $currentVersion = $this->versionService->getCurrentVersion();
        $prefs = $prefsRepository->findOneBy(['utilisateur' => $user]);
        $derniereVersionVue = null !== $prefs ? $prefs->getDerniereVersionVue() : null;

        return $this->render('changelog/banner.html.twig', [
            'show' => null !== $currentVersion && $currentVersion !== $derniereVersionVue,
            'currentVersion' => $currentVersion,
        ]);
    }

    /**
     * @Route("/api/changelog/dismiss", name="changelog_dismiss", methods={"POST"})
     */
    public function dismiss(
        Request $request,
        UtilisateurPreferencesRepository $prefsRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var \App\Entity\Utilisateur|null $user */
        $user = $this->getUser();

        if (null === $user) {
            return new JsonResponse(['success' => false], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isCsrfTokenValid('changelog_dismiss', $request->request->get('_token'))) {
            return new JsonResponse(['success' => false], Response::HTTP_FORBIDDEN);
        }

        $prefs = $prefsRepository->findOneBy(['utilisateur' => $user]);

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
