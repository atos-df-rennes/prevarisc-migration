<?php

namespace App\EventSubscriber;

use App\Controller\DossierController;
use App\Repository\CapsuleRubriqueRepository;
use App\Repository\DossierAffectationRepository;
use App\Repository\DossierRepository;
use App\Service\DateAffectation;
use App\Service\Dossier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class DossierLateralNavigationSubscriber implements EventSubscriberInterface
{
    /** @var DossierRepository */
    private $dossierRepository;

    /** @var DossierAffectationRepository */
    private $dossierAffectationRepository;

    /** @var CapsuleRubriqueRepository */
    private $capsuleRubriqueRepository;

    /** @var DateAffectation */
    private $dateAffectation;

    /** @var Dossier */
    private $serviceDossier;

    public function __construct(
        DossierRepository $dossierRepository,
        DossierAffectationRepository $dossierAffectationRepository,
        CapsuleRubriqueRepository $capsuleRubriqueRepository,
        DateAffectation $dateAffectation,
        Dossier $serviceDossier
    ) {
        $this->dossierRepository = $dossierRepository;
        $this->dossierAffectationRepository = $dossierAffectationRepository;
        $this->capsuleRubriqueRepository = $capsuleRubriqueRepository;
        $this->dateAffectation = $dateAffectation;
        $this->serviceDossier = $serviceDossier;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if (!$controller instanceof DossierController) {
            return;
        }

        $idDossier = filter_var($event->getRequest()->attributes->get('id'), FILTER_VALIDATE_INT);
        $dossier = $this->dossierRepository->find($idDossier);

        if (null === $dossier) {
            throw new NotFoundHttpException(\sprintf("Le dossier ayant pour identifiant %s n'existe pas.", $idDossier));
        }

        $dossierAffectation = $this->dossierAffectationRepository->findOneBy(['dossier' => $dossier]);
        $dateCommission = null !== $dossierAffectation ? $dossierAffectation->getDateCommission() : null;
        $idDateCommission = $this->dateAffectation->getIdDateCommission($dossier, $dateCommission);

        $event->getRequest()->attributes->set('id_date_commission', $idDateCommission);

        $event->getRequest()->attributes->set('nombre_nouvelles_pieces_jointes', $this->serviceDossier->nombreNouvellesPiecesJointes($dossier));

        $capsuleVerificationsTechniques = $this->capsuleRubriqueRepository->findOneBy(['nomInterne' => 'descriptifVerificationsTechniques']);
        $capsuleEffectifsDegagements = $this->capsuleRubriqueRepository->findOneBy(['nomInterne' => 'effectifsDegagementsDossier']);
        $event->getRequest()->attributes->set('libelle_verifications_techniques', null !== $capsuleVerificationsTechniques ? $capsuleVerificationsTechniques->getNom() : 'Vérifications techniques');
        $event->getRequest()->attributes->set('libelle_effectifs_degagements', null !== $capsuleEffectifsDegagements ? $capsuleEffectifsDegagements->getNom() : 'Effectifs et Dégagements');
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
