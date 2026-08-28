<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Service\Commission\IcalendarSyncService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Endpoint de synchronisation webcal pour les clients calendrier (Outlook, Google Calendar…).
 *
 * Port de Api_CalendarController + Api_Service_Calendar::sync() (version simplifiée).
 * Fonctionne SANS session utilisateur — authentification par clé de sécurité dans l'URL.
 */
class IcalendarController extends AbstractController
{
    public function __construct(private readonly UtilisateurRepository $utilisateurRepository, private readonly IcalendarSyncService $icalendarSyncService)
    {
    }

    /**
     * Endpoint de synchronisation webcal.
     *
     * Port de Api_CalendarController::indexAction() + Api_Service_Calendar::sync().
     *
     * Paramètres GET :
     * - userid     (int)         : ID de l'utilisateur (préventionniste)
     * - key        (string)      : Clé de sécurité (doit correspondre à PREVARISC_SECURITY_KEY)
     * - commission (int|null)    : Filtre par commission (optionnel)
     */
    #[Route(path: '/api/commissions/icalendar', name: 'api_icalendar_sync', methods: ['GET'])]
    public function sync(
        #[MapQueryParameter] string $key = '',
        #[MapQueryParameter] string $userid = '',
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $commission = null,
    ): Response {
        // Vérification de la clé de sécurité
        $securityKey = (string) (getenv('PREVARISC_SECURITY_KEY') ?: '');

        if ('' === $securityKey || !hash_equals($securityKey, $key)) {
            return new Response('Accès refusé', Response::HTTP_FORBIDDEN);
        }

        if ('' === $userid) {
            return new Response('Paramètre userid requis', Response::HTTP_BAD_REQUEST);
        }

        $userId = (int) $userid;

        $commissionId = $commission;

        // Vérification du privilège calendar_view_all : si l'utilisateur peut tout voir,
        // on retourne tout sans filtre utilisateur (userId=null).
        // Le filtre commission reste toujours honoré, même pour les admins.
        $utilisateur = $this->utilisateurRepository->find($userId);
        $hasViewAllPrivilege = null !== $utilisateur && $this->hasCalendarViewAllPrivilege($utilisateur);
        $effectiveUserId = $hasViewAllPrivilege ? null : $userId;
        $effectiveCommissionId = $commissionId;

        $icsContent = $this->icalendarSyncService->generateFeed($effectiveUserId, $effectiveCommissionId, $hasViewAllPrivilege);

        $response = new Response($icsContent);
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');

        return $response;
    }

    /**
     * Vérifie si l'utilisateur possède le privilège calendar_view_all.
     * Port de l'ACL Zend — équivalent du PrivilegeVoter sans session.
     */
    private function hasCalendarViewAllPrivilege(Utilisateur $utilisateur): bool
    {
        $groupe = $utilisateur->getGroupe();
        if (null === $groupe) {
            return false;
        }

        foreach ($groupe->getPrivileges() as $privilege) {
            if ('calendar_view_all' === $privilege->getName()) {
                return true;
            }
        }

        return false;
    }
}
