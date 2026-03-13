<?php

namespace App\Controller\Contract;

/**
 * Marque un controller comme nécessitant l'injection des données de navigation
 * latérale du dossier (via DossierLateralNavigationSubscriber).
 *
 * Pour exclure une action spécifique (ex: route Ajax), ajouter dans l'annotation @Route :
 *   defaults={"_dossier_lateral_nav": false}
 */
interface DossierLateralNavigationInterface
{
}
