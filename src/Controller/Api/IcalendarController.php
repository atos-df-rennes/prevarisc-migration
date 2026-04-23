<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Service\Commission\IcalendarSyncService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Endpoint de synchronisation webcal pour les clients calendrier (Outlook, Google Calendar…).
 *
 * Port de Api_CalendarController + Api_Service_Calendar::sync() (version simplifiée).
 * Fonctionne SANS session utilisateur — authentification par clé de sécurité dans l'URL.
 */
class IcalendarController extends AbstractController
{
    /**
     * @var UtilisateurRepository
     */
    private $utilisateurRepository;

    /**
     * @var IcalendarSyncService
     */
    private $icalendarSyncService;

    public function __construct(
        UtilisateurRepository $utilisateurRepository,
        IcalendarSyncService $icalendarSyncService
    ) {
        $this->utilisateurRepository = $utilisateurRepository;
        $this->icalendarSyncService = $icalendarSyncService;
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
     *
     * @Route("/api/commissions/icalendar", name="api_icalendar_sync", methods={"GET"})
     */
    public function sync(Request $request): Response
    {
        // Vérification de la clé de sécurité
        $keyRaw = $request->query->get('key', '');
        $key = \is_string($keyRaw) ? $keyRaw : '';
        $securityKey = (string) (getenv('PREVARISC_SECURITY_KEY') ?: '');

        if ('' === $securityKey || !hash_equals($securityKey, $key)) {
            return new Response('Accès refusé', Response::HTTP_FORBIDDEN);
        }

        /** @var string|null $rawUserId */
        $rawUserId = $request->query->get('userid');
        if (null === $rawUserId || '' === $rawUserId) {
            return new Response('Paramètre userid requis', Response::HTTP_BAD_REQUEST);
        }

        $userId = (int) $rawUserId;

        /** @var string|null $rawCommissionId */
        $rawCommissionId = $request->query->get('commission');
        $commissionId = null !== $rawCommissionId && '' !== $rawCommissionId ? (int) $rawCommissionId : null;

        // Vérification du privilège calendar_view_all : si l'utilisateur peut tout voir,
        // on retourne tout sans filtre utilisateur (userId=null).
        // Le filtre commission reste toujours honoré, même pour les admins.
        $utilisateur = $this->utilisateurRepository->find($userId);
        $effectiveUserId = null !== $utilisateur && $this->hasCalendarViewAllPrivilege($utilisateur)
            ? null
            : $userId;
        $effectiveCommissionId = $commissionId;

        $icsContent = $this->icalendarSyncService->generateFeed($effectiveUserId, $effectiveCommissionId);

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
