<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use App\Constant\DossierNature;
use App\Constant\DossierType as DossierTypeConstant;
use App\Controller\Contract\DossierPrescriptionsAccessInterface;
use App\Repository\DossierRepository;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Interdit l'accès aux routes de prescriptions d'un dossier dont la nature
 * ou le type ne permet pas les prescriptions (cf. templates/layouts/dossier.html.twig).
 */
class PrescriptionsAccessSubscriber
{
    public function __construct(private readonly DossierRepository $dossierRepository)
    {
    }

    #[AsEventListener(event: KernelEvents::CONTROLLER)]
    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if (!$controller instanceof DossierPrescriptionsAccessInterface) {
            return;
        }

        $idDossier = filter_var($event->getRequest()->attributes->get('id'), FILTER_VALIDATE_INT);
        $dossier = $this->dossierRepository->find($idDossier);

        if (null === $dossier) {
            throw new NotFoundHttpException(\sprintf("Le dossier ayant pour identifiant %s n'existe pas.", $idDossier));
        }

        $nature = $dossier->getNature();
        $type = $dossier->getType();

        $natureSansPrescriptions = null !== $nature && in_array($nature->getId(), DossierNature::NATURES_SANS_PRESCRIPTIONS, true);
        $typeSansPrescriptions = null !== $type && in_array($type->getId(), DossierTypeConstant::TYPES_SANS_PRESCRIPTIONS, true);

        if ($natureSansPrescriptions || $typeSansPrescriptions) {
            throw new NotFoundHttpException('Les prescriptions ne sont pas disponibles pour ce dossier.');
        }
    }
}
