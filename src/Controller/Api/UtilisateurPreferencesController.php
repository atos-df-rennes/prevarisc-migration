<?php

namespace App\Controller\Api;

use App\Entity\Utilisateur;
use App\Entity\UtilisateurPreferences;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API pour les préférences utilisateur.
 * Port direct du module api/UserController (legacy) — action preferences.
 */
class UtilisateurPreferencesController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Sauvegarde les préférences utilisateur (blocs dashboard).
     *
     * @Route("/api/utilisateur/preferences", name="api_utilisateur_preferences", methods={"POST"})
     */
    public function update(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $preferences = $request->request->all();

        if (empty($preferences)) {
            $body = $request->getContent();
            $decoded = json_decode($body, true);
            $preferences = is_array($decoded) ? $decoded : [];
        }

        $userPreferences = $user->getPreferences();

        if (null === $userPreferences) {
            $userPreferences = new UtilisateurPreferences();
            $userPreferences->setUtilisateur($user);
            $this->entityManager->persist($userPreferences);
        }

        if (isset($preferences['DASHBOARD_BLOCS']) && is_array($preferences['DASHBOARD_BLOCS'])) {
            $userPreferences->setBlocsDashboard(json_encode($preferences['DASHBOARD_BLOCS']) ?: null);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'DASHBOARD_BLOCS' => $userPreferences->getBlocsDashboard(),
        ]);
    }
}
