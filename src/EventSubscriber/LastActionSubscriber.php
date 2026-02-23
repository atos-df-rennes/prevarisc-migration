<?php

namespace App\EventSubscriber;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Security;

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

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $utilisateur = $this->security->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            return;
        }

        $utilisateur->setLastAction(new \DateTime());

        try {
            $this->entityManager->persist($utilisateur);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            $this->logger->error('LastActionSubscriber: '.$exception->getMessage());
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => 'onKernelRequest',
        ];
    }
}
