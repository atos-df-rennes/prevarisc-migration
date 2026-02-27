<?php

namespace App\Service;

/**
 * Service de gestion des changements d'établissement.
 *
 * Contient les définitions de balises pour templates email.
 * Équivalent legacy : Service_Changement
 */
final class Changement
{
    /**
     * Définition des balises disponibles pour les templates d'email.
     *
     * Chaque balise contient :
     * - description : Description lisible de la balise
     * - method : Méthode à appeler pour récupérer la valeur (ou 'SPECIAL' pour traitement spécial)
     *
     * Équivalent legacy : Service_Changement::BALISES
     * Structure adaptée Symfony : 'method' au lieu de 'model'/'champ'
     *
     * @var array<string, array{description: string, method: string}>
     */
    public const BALISES = [
        '{activitePrincipaleEtablissement}' => [
            'description' => "L'activité principale de l'établissement",
            'method' => 'getTypeActivite', // Sur EtablissementInformations
        ],
        '{categorieEtablissement}' => [
            'description' => "La catégorie de l'établissement",
            'method' => 'getCategorie', // Sur EtablissementInformations
        ],
        '{etablissementAvis}' => [
            'description' => "L'avis de l'établissement",
            'method' => 'SPECIAL', // Géré par AvisService
        ],
        '{etablissementLibelle}' => [
            'description' => "Le libellé de l'établissement",
            'method' => 'getLibelle', // Sur EtablissementInformations
        ],
        '{etablissementNumeroId}' => [
            'description' => "Le numéro Id de l'établissement",
            'method' => 'getNumero', // Sur Etablissement
        ],
        '{etablissementStatut}' => [
            'description' => "Le statut (Ouvert ou Fermé) de l'établissement",
            'method' => 'getStatut', // Sur EtablissementInformations
        ],
        '{typePrincipalEtablissement}' => [
            'description' => "Le type principal de l'établissement",
            'method' => 'getType', // Sur EtablissementInformations
        ],
    ];

    /**
     * Retourne la liste des balises disponibles.
     *
     * Équivalent legacy : Service_Changement::getBalises()
     *
     * @return array<string, array{description: string, method: string}>
     */
    public function getBalises(): array
    {
        return self::BALISES;
    }
}
