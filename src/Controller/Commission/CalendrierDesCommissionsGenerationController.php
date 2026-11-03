<?php

namespace App\Controller\Commission;

use App\Entity\DateCommission;
use App\Service\Commission\CommissionDocumentGenerationService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Génération des documents de commission (convocation, ODJ, PV, compte-rendu).
 */
#[IsGranted('generation_doc_com')]
final class CalendrierDesCommissionsGenerationController extends AbstractController
{
    public function __construct(private readonly CommissionDocumentGenerationService $generationService, private readonly ManagerRegistry $managerRegistry)
    {
    }

    /**
     * Génère les convocations pour une date de commission.
     */
    #[Route(path: '/commission/calendrier/{dateCommId}/generation/convocation', name: 'commission_calendrier_generation_convocation', requirements: ['dateCommId' => '\d+'], methods: ['POST'])]
    public function generationConvocation(int $dateCommId, Request $request): JsonResponse
    {
        $errorResponse = $this->validateRequest($dateCommId, $request, 'generation_convocation');
        if ($errorResponse instanceof JsonResponse) {
            return $errorResponse;
        }

        $results = $this->generationService->generateConvocations($dateCommId);

        return $this->buildJsonResponse($results);
    }

    /**
     * Génère les ordres du jour pour une date de commission.
     */
    #[Route(path: '/commission/calendrier/{dateCommId}/generation/odj', name: 'commission_calendrier_generation_odj', requirements: ['dateCommId' => '\d+'], methods: ['POST'])]
    public function generationOdj(int $dateCommId, Request $request): JsonResponse
    {
        $errorResponse = $this->validateRequest($dateCommId, $request, 'generation_odj');
        if ($errorResponse instanceof JsonResponse) {
            return $errorResponse;
        }

        $results = $this->generationService->generateOrdreDuJour($dateCommId);

        return $this->buildJsonResponse($results);
    }

    /**
     * Génère les procès-verbaux pour une date de commission.
     */
    #[Route(path: '/commission/calendrier/{dateCommId}/generation/pv', name: 'commission_calendrier_generation_pv', requirements: ['dateCommId' => '\d+'], methods: ['POST'])]
    public function generationPv(int $dateCommId, Request $request): JsonResponse
    {
        $errorResponse = $this->validateRequest($dateCommId, $request, 'generation_pv');
        if ($errorResponse instanceof JsonResponse) {
            return $errorResponse;
        }

        $results = $this->generationService->generateProcesVerbal($dateCommId);

        return $this->buildJsonResponse($results);
    }

    /**
     * Génère le compte-rendu pour une date de commission.
     */
    #[Route(path: '/commission/calendrier/{dateCommId}/generation/compte-rendu', name: 'commission_calendrier_generation_compte_rendu', requirements: ['dateCommId' => '\d+'], methods: ['POST'])]
    public function generationCompteRendu(int $dateCommId, Request $request): JsonResponse
    {
        $errorResponse = $this->validateRequest($dateCommId, $request, 'generation_compte_rendu');
        if ($errorResponse instanceof JsonResponse) {
            return $errorResponse;
        }

        $results = $this->generationService->generateCompteRendu($dateCommId);

        return $this->buildJsonResponse($results);
    }

    /**
     * Valide la requête (CSRF + existence dateCommission).
     *
     * @return JsonResponse|null null si la validation est OK, une réponse d'erreur sinon
     */
    private function validateRequest(int $dateCommId, Request $request, string $csrfId): ?JsonResponse
    {
        /** @var string $token */
        $token = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid($csrfId.'_'.$dateCommId, $token)) {
            return new JsonResponse([
                'success' => false,
                'summary' => 'Token de sécurité invalide.',
                'results' => [],
            ], Response::HTTP_FORBIDDEN);
        }

        /** @var DateCommission|null $dateCommission */
        $dateCommission = $this->managerRegistry->getManager()->find(DateCommission::class, $dateCommId);
        if (null === $dateCommission) {
            return new JsonResponse([
                'success' => false,
                'summary' => 'Date de commission introuvable.',
                'results' => [],
            ], Response::HTTP_NOT_FOUND);
        }

        return null;
    }

    /**
     * Construit la réponse JSON récapitulative.
     *
     * @param array<int, array{memberId: int, memberLabel: string, status: string, message: string, pieceJointeId: int|null}> $results
     */
    private function buildJsonResponse(array $results): JsonResponse
    {
        $summary = empty($results) ? 'Aucun document à générer.' : '';

        return new JsonResponse([
            'summary' => $summary,
            'results' => $results,
        ]);
    }
}
