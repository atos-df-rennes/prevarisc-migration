<?php

namespace App\EventSubscriber;

use App\Controller\EtablissementController;
use App\Repository\CapsuleRubriqueRepository;
use App\Repository\EtablissementRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LateralNavigationSubscriber implements EventSubscriberInterface
{
    /** @var EtablissementRepository */
    private $etablissementRepository;

    /** @var CapsuleRubriqueRepository */
    private $capsuleRubriqueRepository;

    public function __construct(
        EtablissementRepository $etablissementRepository,
        CapsuleRubriqueRepository $capsuleRubriqueRepository
    ) {
        $this->etablissementRepository = $etablissementRepository;
        $this->capsuleRubriqueRepository = $capsuleRubriqueRepository;
    }

    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if ($controller instanceof EtablissementController) {
            $etablissement = $this->etablissementRepository->find($event->getRequest()->attributes->get('id'));

            // Récupère récursivement le parent de l'établissement
            $etablissementCourant = $etablissement;
            $parents = [];
            do {
                $parent = $etablissementCourant->getParent();

                if ($parent !== null) {
                    $parents[] = $parent;
                    $etablissementCourant = $this->etablissementRepository->find($parent->getId());
                }
            } while ($parent !== null);
            $parents = array_reverse($parents);

            $event->getRequest()->attributes->set('parents_etablissement', $parents);

            $event->getRequest()->attributes->set('libelle_descriptif', $this->capsuleRubriqueRepository->findOneBy(['nomInterne' => 'descriptifEtablissement'])->getNom());
            $event->getRequest()->attributes->set('libelle_effectifs_degagements', $this->capsuleRubriqueRepository->findOneBy(['nomInterne' => 'effectifsDegagementsEtablissement'])->getNom());
            $event->getRequest()->attributes->set('has_avis_derogations', $etablissement->hasAvisDeogations());
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
