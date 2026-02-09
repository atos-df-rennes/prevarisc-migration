<?php

namespace App\EventSubscriber;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Security\Core\Security;

/**
 * Met à jour la date de dernière action de l'utilisateur connecté.
 */
class LastActionSubscriber implements EventSubscriberInterface
{
    /** @var Security */
    private $security;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var LoggerInterface */
    private $logger;

    /** @var UtilisateurRepository */
    private $utilisateurRepository;

    public function __construct(
        Security $security,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        UtilisateurRepository $utilisateurRepository
    )
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->utilisateurRepository = $utilisateurRepository;
    }

    /**
     * Utilise kernel.terminate pour exécuter après l'envoi de la réponse HTTP.
     * Utilise clear() pour ne flush() que la modification effectuée dans cette méthode.
     * Cela prévient notamment la mise à jour d'une entité comportant des erreurs de validation.
     */
    public function onKernelTerminate(TerminateEvent $event): void
    {
        $this->entityManager->clear();

        $utilisateur = $this->security->getUser();

        if (!$utilisateur instanceof Utilisateur || null === $utilisateur->getId()) {
            return;
        }

        $utilisateur = $this->utilisateurRepository->find($utilisateur->getId());
        $utilisateur->setLastAction(new \DateTime());

        try {
            $this->entityManager->persist($utilisateur);
            $this->entityManager->flush();
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
