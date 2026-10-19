<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gestion des dates de dernière visite pour les notifications Plat'AU.
 * Port direct de Service_Notification (legacy).
 */
final class NotificationService
{
    public const DASHBOARD_NAMESPACE = 'DERNIERE_VISITE_DASHBOARD';

    public const PIECES_DOSSIER_NAMESPACE = 'DERNIERE_VISITE_PIECES_DOSSIER';

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Retourne la date de dernière visite de la page pour le namespace donné.
     * Retourne une date très ancienne si jamais consultée (pour que tout soit "nouveau").
     */
    public function getLastVisitDate(Utilisateur $user, string $namespace): \DateTimeInterface
    {
        if (self::DASHBOARD_NAMESPACE === $namespace) {
            return $user->getDerniereVisiteDashboard() ?? new \DateTime('2000-01-01 00:00:00');
        }

        return $user->getDerniereVisitePiecesDossier() ?? new \DateTime('2000-01-01 00:00:00');
    }

    /**
     * Met à jour la date de dernière visite pour le namespace donné.
     */
    public function setLastVisitDate(Utilisateur $user, string $namespace): void
    {
        $now = new \DateTime();

        if (self::DASHBOARD_NAMESPACE === $namespace) {
            $user->setDerniereVisiteDashboard($now);
        } else {
            $user->setDerniereVisitePiecesDossier($now);
        }

        $this->entityManager->flush();
    }
}
