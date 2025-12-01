<?php

namespace App\EventSubscriber;

use App\Controller\DossierController;
use App\Controller\EtablissementController;
use App\Repository\CapsuleRubriqueRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DossierLateralNavigationSubscriber implements EventSubscriberInterface
{
    /** @var CapsuleRubriqueRepository */
    private $capsuleRubriqueRepository;

    public function __construct(
        CapsuleRubriqueRepository $capsuleRubriqueRepository
    ) {
        $this->capsuleRubriqueRepository = $capsuleRubriqueRepository;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if ($controller instanceof DossierController) {
            $capsuleVerificationsTechniques = $this->capsuleRubriqueRepository->findOneBy(['nomInterne' => 'descriptifVerificationsTechniques']);
            $capsuleEffectifsDegagements = $this->capsuleRubriqueRepository->findOneBy(['nomInterne' => 'effectifsDegagementsDossier']);
            $event->getRequest()->attributes->set('libelle_verifications_techniques', null !== $capsuleVerificationsTechniques ? $capsuleVerificationsTechniques->getNom() : 'Vérifications techniques');
            $event->getRequest()->attributes->set('libelle_effectifs_degagements', null !== $capsuleEffectifsDegagements ? $capsuleEffectifsDegagements->getNom() : 'Effectifs et Dégagements');
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
