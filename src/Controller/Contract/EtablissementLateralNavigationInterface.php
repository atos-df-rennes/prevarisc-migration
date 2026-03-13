<?php

namespace App\Controller\Contract;

/**
 * Marque un controller comme nécessitant l'injection des données de navigation
 * latérale de l'établissement (via EtablissementLateralNavigationSubscriber).
 *
 * Pour exclure une action spécifique (ex: route Ajax), ajouter dans l'annotation @Route :
 *   defaults={"_etablissement_lateral_nav": false}
 */
interface EtablissementLateralNavigationInterface
{
}
