<?php

namespace App\Service\Alerte;

/**
 * Service de gestion des alertes par email.
 *
 * Équivalent legacy : Service_Alerte
 */
class AlerteService
{
    /**
     * Type de changement pour avis de commission.
     */
    public const TYPE_CHANGEMENT_AVIS = 2;

    /**
     * Génère le lien HTML "Alerter" pour les flashMessages.
     *
     * Équivalent legacy : Service_Alerte::getLink()
     *
     * @param int      $idTypeChangement Type de changement (2 = avis)
     * @param int|null $idEtablissement  ID établissement concerné
     *
     * @return string Lien HTML avec data-attributes pour le JavaScript
     */
    public function getLink(int $idTypeChangement, ?int $idEtablissement = null): string
    {
        $etabData = '';
        if (null !== $idEtablissement) {
            $etabData = sprintf(' data-ets="%d"', $idEtablissement);
        }

        return sprintf(
            '<a data-value="%d"%s class="pull-right alerte-link"><i class="icon-bell icon-black"></i>Alerter</a>',
            $idTypeChangement,
            $etabData
        );
    }
}
