<?php

namespace App\EventSubscriber;

use App\Controller\EtablissementController;
use App\Repository\EtablissementRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LateralNavigationSubscriber implements EventSubscriberInterface
{
    /** @var EtablissementRepository */
    private $etablissementRepository;

    public function __construct(EtablissementRepository $etablissementRepository) {
        $this->etablissementRepository = $etablissementRepository;
    }

    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if ($controller instanceof EtablissementController) {
            $etablissement = $this->etablissementRepository->find($event->getRequest()->attributes->get('id'));

            $parents = [];
            do {
                $parent = $etablissement->getParent();

                if ($parent !== null) {
                    $parents[] = $parent;
                    $etablissement = $this->etablissementRepository->find($parent->getId());
                }
            } while ($parent !== null);
            $parents = array_reverse($parents);

            $event->getRequest()->attributes->set('parents_etablissement', $parents);

//            $event->getRequest()->attributes->set('libelle_descriptif', 'TEST');
//            $event->getRequest()->attributes->set('libelle_effectifs_degagements', 'Effectifs');
//            $event->getRequest()->attributes->set('has_avis_derogations', true);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
