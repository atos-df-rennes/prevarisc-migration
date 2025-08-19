<?php

namespace App\EventSubscriber;

use App\Controller\EtablissementController;
use App\Repository\CapsuleRubriqueRepository;
use App\Repository\EtablissementRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if ($controller instanceof EtablissementController) {
            $idEtablissement = filter_var($event->getRequest()->attributes->get('id'), FILTER_VALIDATE_INT);
            $etablissement = $this->etablissementRepository->find($idEtablissement);

            if (null === $etablissement) {
                throw new NotFoundHttpException("L'établissement ayant pour identifiant {$idEtablissement} n'existe pas.");
            }

            // Récupère récursivement le parent de l'établissement
            $etablissementCourant = $etablissement;
            $parents = [];

            do {
                if (null === $etablissementCourant) {
                    break;
                }

                $parent = $etablissementCourant->getParent();

                if ($parent !== null) {
                    $parents[] = $parent;
                    $etablissementCourant = $this->etablissementRepository->find($parent->getId());
                }
            }
            while ($parent !== null);

            $parents = array_reverse($parents);
            $event->getRequest()->attributes->set('parents_etablissement', $parents);

            $capsuleDescriptifEtablissement = $this->capsuleRubriqueRepository->findOneBy(['nomInterne' => 'descriptifEtablissement']);
            $capsuleEffectifsDegagements = $this->capsuleRubriqueRepository->findOneBy(['nomInterne' => 'effectifsDegagementsEtablissement']);
            $event->getRequest()->attributes->set('libelle_descriptif', null !== $capsuleDescriptifEtablissement ? $capsuleDescriptifEtablissement->getNom() : 'Descriptif');
            $event->getRequest()->attributes->set('libelle_effectifs_degagements', null !== $capsuleEffectifsDegagements ? $capsuleEffectifsDegagements->getNom() : 'Effectifs et Dégagements');

            $event->getRequest()->attributes->set('has_avis_derogations', $etablissement->hasAvisDerogations());
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
