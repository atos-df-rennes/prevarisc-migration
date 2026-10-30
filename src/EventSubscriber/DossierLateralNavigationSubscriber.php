<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use App\Constant\NomInterneCapsule;
use App\Controller\Contract\DossierLateralNavigationInterface;
use App\Repository\CapsuleRubriqueRepository;
use App\Repository\DossierAffectationRepository;
use App\Repository\DossierRepository;
use App\Service\DateAffectation;
use App\Service\Dossier;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class DossierLateralNavigationSubscriber
{
    public function __construct(private readonly DossierRepository $dossierRepository, private readonly DossierAffectationRepository $dossierAffectationRepository, private readonly CapsuleRubriqueRepository $capsuleRubriqueRepository, private readonly DateAffectation $dateAffectation, private readonly Dossier $serviceDossier)
    {
    }
    #[AsEventListener(event: KernelEvents::CONTROLLER)]
    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if (!$controller instanceof DossierLateralNavigationInterface) {
            return;
        }

        if (false === $event->getRequest()->attributes->get('_dossier_lateral_nav', true)) {
            return;
        }

        $idDossier = filter_var($event->getRequest()->attributes->get('id'), FILTER_VALIDATE_INT);
        $dossier = $this->dossierRepository->find($idDossier);

        if (null === $dossier) {
            throw new NotFoundHttpException(\sprintf("Le dossier ayant pour identifiant %s n'existe pas.", $idDossier));
        }

        $dossierAffectation = $this->dossierAffectationRepository->findOneBy(['dossier' => $dossier]);
        $dateCommission = $dossierAffectation?->getDateCommission();
        $idDateCommission = $this->dateAffectation->getIdDateCommission($dossier, $dateCommission);

        $event->getRequest()->attributes->set('id_date_commission', $idDateCommission);

        $event->getRequest()->attributes->set('nombre_nouvelles_pieces_jointes', $this->serviceDossier->nombreNouvellesPiecesJointes($dossier));

        $capsuleVerificationsTechniques = $this->capsuleRubriqueRepository->findOneBy(['nomInterne' => NomInterneCapsule::DESCRIPTIF_VERIFICATIONS_TECHNIQUES]);
        $capsuleEffectifsDegagements = $this->capsuleRubriqueRepository->findOneBy(['nomInterne' => NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER]);
        $event->getRequest()->attributes->set('libelle_verifications_techniques', null !== $capsuleVerificationsTechniques ? $capsuleVerificationsTechniques->getNom() : 'Vérifications techniques');
        $event->getRequest()->attributes->set('libelle_effectifs_degagements', null !== $capsuleEffectifsDegagements ? $capsuleEffectifsDegagements->getNom() : 'Effectifs et Dégagements');
    }
}
