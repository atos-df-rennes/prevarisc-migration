<?php

namespace App\EventSubscriber;

use App\Constant\NomInterneCapsule;
use App\Controller\Contract\EtablissementLateralNavigationInterface;
use App\Repository\CapsuleRubriqueRepository;
use App\Repository\EtablissementRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EtablissementLateralNavigationSubscriber implements EventSubscriberInterface
{
    public function __construct(private EtablissementRepository $etablissementRepository, private CapsuleRubriqueRepository $capsuleRubriqueRepository, private AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if (!$controller instanceof EtablissementLateralNavigationInterface) {
            return;
        }

        if (false === $event->getRequest()->attributes->get('_etablissement_lateral_nav', true)) {
            return;
        }

        $idEtablissement = filter_var($event->getRequest()->attributes->get('id'), FILTER_VALIDATE_INT);
        if (false === $idEtablissement) {
            $idEtablissement = filter_var($event->getRequest()->request->get('id'), FILTER_VALIDATE_INT);
        }

        $etablissement = $this->etablissementRepository->find($idEtablissement);

        if (null === $etablissement) {
            throw new NotFoundHttpException(\sprintf("L'établissement ayant pour identifiant %s n'existe pas.", $idEtablissement));
        }

        if (null !== $etablissement->getDateSuppression()) {
            throw new NotFoundHttpException(\sprintf("L'établissement ayant pour identifiant %s a été supprimé et n'est pas accessible.", $idEtablissement));
        }

        $isAllowedDeleteEtablissement = $this->authorizationChecker->isGranted('delete_etablissement', $etablissement);

        $event->getRequest()->attributes->set('is_allowed_delete_etablissement', $isAllowedDeleteEtablissement);

        // Récupère récursivement le parent de l'établissement
        $etablissementCourant = $etablissement;
        $parents = [];

        do {
            $parent = $etablissementCourant->getParent();

            if (null !== $parent) {
                $parents[] = $parent;
                $etablissementCourant = $parent;
            }
        } while (null !== $parent);

        $parents = array_reverse($parents);
        $event->getRequest()->attributes->set('parents_etablissement', $parents);

        $capsuleDescriptifEtablissement = $this->capsuleRubriqueRepository->findOneBy(['nomInterne' => NomInterneCapsule::DESCRIPTIF_ETABLISSEMENT]);
        $capsuleEffectifsDegagements = $this->capsuleRubriqueRepository->findOneBy(['nomInterne' => NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_ETABLISSEMENT]);
        $event->getRequest()->attributes->set('libelle_descriptif', null !== $capsuleDescriptifEtablissement ? $capsuleDescriptifEtablissement->getNom() : 'Descriptif');
        $event->getRequest()->attributes->set('libelle_effectifs_degagements', null !== $capsuleEffectifsDegagements ? $capsuleEffectifsDegagements->getNom() : 'Effectifs et Dégagements');

        $event->getRequest()->attributes->set('has_avis_derogations', $this->etablissementRepository->hasAvisDerogationsVisibles($etablissement->getId()));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
