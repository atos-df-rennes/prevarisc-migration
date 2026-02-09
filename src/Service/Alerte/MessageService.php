<?php

namespace App\Service\Alerte;

use App\Entity\Etablissement;

/**
 * Service de génération de message email pour les alertes.
 *
 * Équivalent legacy : Service_Changement::convertMessage()
 *
 * @todo Implémenter génération message avec remplacement balises
 */
class MessageService
{
    /**
     * Génère le message de l'email d'alerte.
     *
     * Équivalent legacy : Service_Changement::convertMessage()
     *
     * @param int           $typeChangement Type de changement (1=statut, 2=avis, 3=classement)
     * @param Etablissement $etablissement  Établissement concerné
     *
     * @return string Message email formaté
     */
    public function generer(int $typeChangement, Etablissement $etablissement): string
    {
        // @todo Implémenter dans Part 3B2.3
        return 'Message par défaut';
    }
}
