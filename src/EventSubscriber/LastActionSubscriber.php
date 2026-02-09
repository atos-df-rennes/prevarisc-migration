<?php

namespace App\EventSubscriber;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Security\Core\Security;

/**
 * Met à jour la date de dernière action de l'utilisateur connecté.
 *
 * Utilise kernel.terminate pour exécuter après l'envoi de la réponse HTTP.
 * Utilise une requête SQL directe pour ne pas impacter l'UnitOfWork Doctrine
 * (évite de flusher accidentellement des entités en cours de modification).
 */
class LastActionSubscriber implements EventSubscriberInterface
{
    /** @var Security */
    private $security;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Security $security, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $utilisateur = $this->security->getUser();

        if (!$utilisateur instanceof Utilisateur || null === $utilisateur->getId()) {
            return;
        }

        try {
            // Utilise une requête SQL directe pour éviter d'impacter l'UnitOfWork
            // et de flusher accidentellement d'autres entités managées
            $conn = $this->entityManager->getConnection();
            $conn->executeStatement(
                'UPDATE utilisateur SET LASTACTION_UTILISATEUR = NOW() WHERE ID_UTILISATEUR = ?',
                [$utilisateur->getId()]
            );
        } catch (\Exception $exception) {
            $this->logger->error('LastActionSubscriber: '.$exception->getMessage());
        }
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // kernel.terminate s'exécute après l'envoi de la réponse HTTP
            'kernel.terminate' => 'onKernelTerminate',
        ];
    }
}
