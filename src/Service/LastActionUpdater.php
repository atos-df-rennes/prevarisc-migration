<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Met à jour la date de dernière action d'un utilisateur.
 *
 * Utilisé depuis LastActionSubscriber (kernel.terminate) et LogoutHandler
 * (avant invalidation du token, pour garantir la mise à jour même au logout).
 */
class LastActionUpdater
{
    public function __construct(private EntityManagerInterface $entityManager, private UtilisateurRepository $utilisateurRepository, private LoggerInterface $logger)
    {
    }

    /**
     * Met à jour LASTACTION_UTILISATEUR pour l'utilisateur donné.
     *
     * Effectue un clear() de l'EntityManager avant le flush pour ne persister
     * que cette modification et prévenir la mise à jour d'entités comportant
     * des erreurs de validation issues de la requête courante.
     */
    public function update(Utilisateur $utilisateur): void
    {
        if (null === $utilisateur->getId()) {
            return;
        }

        $this->entityManager->clear();

        $utilisateur = $this->utilisateurRepository->find($utilisateur->getId());

        if (null === $utilisateur) {
            return;
        }

        $utilisateur->setLastAction(new \DateTime());

        try {
            $this->entityManager->persist($utilisateur);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            $this->logger->error('LastActionUpdater: '.$exception->getMessage());
        }
    }
}
