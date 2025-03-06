<?php

namespace App\EventSubscriber;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Security;

class LastActionSubscriber implements EventSubscriberInterface
{
    /** @var Security */
    private $security;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $utilisateur = $this->security->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            return;
        }

        $utilisateur->setLastAction(new \DateTime());

        $this->entityManager->persist($utilisateur);
        $this->entityManager->flush();
    }

    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => 'onKernelRequest',
        ];
    }
}
