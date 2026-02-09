<?php

declare(strict_types=1);

namespace App\Service\Alerte;

use App\Entity\Etablissement;

/**
 * Service de génération de message email pour les alertes.
 *
 * Remplace les balises dans le template de message avec les valeurs de l'établissement.
 * Équivalent legacy : Service_Changement::convertMessage()
 */
class MessageService
{
    /**
     * Définition des balises disponibles.
     *
     * Mapping des balises vers les accesseurs d'entité.
     * La balise '{etablissementAvis}' est traitée spécialement via AvisService.
     *
     * @var array<string, array{description: string, method: string}>
     */
    private const BALISES = [
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

    /** @var AvisService */
    private $avisService;

    /**
     * @param AvisService $avisService Service utilitaire pour récupération avis
     */
    public function __construct(AvisService $avisService)
    {
        $this->avisService = $avisService;
    }

    /**
     * Génère le message de l'email d'alerte en remplaçant les balises.
     *
     * Équivalent legacy : Service_Changement::convertMessage()
     *
     * Remplace chaque balise du template par la valeur correspondante de l'établissement.
     * Exemple : "{etablissementLibelle}" → "Mairie de Gravigny"
     *
     * @param string        $messageTemplate Template avec balises
     * @param Etablissement $etablissement   Établissement concerné
     *
     * @return string Message avec balises remplacées
     */
    public function generer(string $messageTemplate, Etablissement $etablissement): string
    {
        $replacements = [];

        foreach (self::BALISES as $balise => $config) {
            $valeur = $this->getValeurBalise($config['method'], $etablissement);
            $replacements[$balise] = $valeur;
        }

        return strtr($messageTemplate, $replacements);
    }

    /**
     * Récupère la valeur d'une balise depuis l'établissement.
     *
     * @param string        $method        Nom de la méthode à appeler (ou 'SPECIAL')
     * @param Etablissement $etablissement Établissement concerné
     *
     * @return string Valeur de la balise
     */
    private function getValeurBalise(string $method, Etablissement $etablissement): string
    {
        // Cas spécial : avis via AvisService
        if ('SPECIAL' === $method) {
            return $this->avisService->getTexte($etablissement);
        }

        // Cas spécial : méthode directement sur Etablissement
        if ('getNumero' === $method) {
            return $etablissement->getNumero() ?? '';
        }

        // Autres cas : méthode sur LatestInformations
        $latestInfos = $etablissement->getLatestInformations();
        
        if (!method_exists($latestInfos, $method)) {
            return '';
        }

        /** @var mixed $entity */
        $entity = $latestInfos->$method();

        // Si l'entité a une méthode getLibelle(), l'utiliser
        if (is_object($entity) && method_exists($entity, 'getLibelle')) {
            $libelle = $entity->getLibelle();
            return is_string($libelle) ? $libelle : '';
        }

        // Sinon retourner la valeur directe (si string)
        return is_string($entity) ? $entity : '';
    }
}
