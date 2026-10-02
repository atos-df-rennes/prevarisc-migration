<?php

declare(strict_types=1);

namespace App\Service\Alerte;

use App\Entity\Etablissement;
use App\Service\Changement;

/**
 * Service de génération de message email pour les alertes.
 *
 * Remplace les balises dans le template de message avec les valeurs de l'établissement.
 * Équivalent legacy : Service_Changement::convertMessage()
 */
class MessageService
{
    /**
     * @param AvisService $avisService Service utilitaire pour récupération avis
     */
    public function __construct(private readonly AvisService $avisService)
    {
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

        foreach (Changement::BALISES as $balise => $config) {
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

        if ('getCategorie' === $method) {
            $categorie = $etablissement->getCategorieEffective();

            return null !== $categorie ? ($categorie->getLibelle() ?? '') : '';
        }

        // Autres cas : méthode sur LatestInformations
        $latestInfos = $etablissement->getLatestInformations();

        if (!method_exists($latestInfos, $method)) {
            return '';
        }

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
