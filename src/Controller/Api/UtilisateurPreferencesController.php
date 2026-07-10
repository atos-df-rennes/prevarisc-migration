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
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Sauvegarde les préférences utilisateur (blocs dashboard).
     *
     * @Route("/api/utilisateur/preferences", name="api_utilisateur_preferences", methods={"POST"}, options={"expose"=true})
     */
    public function update(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $preferences = $request->request->all();

        if ([] === $preferences) {
            $body = $request->getContent();
            $decoded = json_decode($body, true);
            $preferences = is_array($decoded) ? $decoded : [];
        }

        // Le JS poste {preferences: {DASHBOARD_BLOCS: [...]}} — extraire le niveau "preferences"
        $prefsData = isset($preferences['preferences']) && is_array($preferences['preferences'])
            ? $preferences['preferences']
            : $preferences;

        $userPreferences = $user->getPreferences();

        if (null === $userPreferences) {
            $userPreferences = new UtilisateurPreferences();
            $userPreferences->setUtilisateur($user);
            $this->entityManager->persist($userPreferences);
        }

        if (array_key_exists('DASHBOARD_BLOCS', $prefsData)) {
            $blocs = $prefsData['DASHBOARD_BLOCS'];
            if (is_array($blocs) && [] !== $blocs) {
                $userPreferences->setBlocsDashboard(json_encode($blocs) ?: null);
            } else {
                $userPreferences->setBlocsDashboard(null);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'DASHBOARD_BLOCS' => $userPreferences->getBlocsDashboard(),
        ]);
    }
}
