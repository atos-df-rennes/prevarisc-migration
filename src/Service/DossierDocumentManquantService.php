<?php

namespace App\Service;

use App\Entity\Dossier;

/**
 * Service gérant la logique métier des documents manquants.
 */
final class DossierDocumentManquantService
{
    /**
     * Attribue un numéro (NUM_DOCSMANQUANT) aux documents manquants qui n'en ont pas encore.
     * Les numéros sont attribués de façon séquentielle à la suite du numéro le plus élevé déjà utilisé
     * par le dossier, à l'image du comportement de l'application legacy.
     *
     * @param Dossier $dossier Le dossier contenant les documents
     */
    public function assignerNumeros(Dossier $dossier): void
    {
        $documentsManquants = $dossier->getDocumentsManquants();

        $numeroMax = 0;
        foreach ($documentsManquants as $document) {
            $numero = $document->getNumero();
            if (null !== $numero && '' !== $numero) {
                $numeroMax = max($numeroMax, (int) $numero);
            }
        }

        foreach ($documentsManquants as $document) {
            $numero = $document->getNumero();
            if (null === $numero || '' === $numero) {
                $document->setNumero((string) ++$numeroMax);
            }
        }
    }

    /**
     * Organise les documents manquants d'un dossier par ordre décroissant.
     * Sépare les documents actifs (sans date réception) des documents historiques (avec date réception).
     *
     * @param Dossier $dossier Le dossier contenant les documents
     */
    public function organizeDocumentsManquants(Dossier $dossier): void
    {
        $documentsManquants = $dossier->getDocumentsManquants();

        if (0 === $documentsManquants->count()) {
            return;
        }

        // Récupérer tous les documents sous forme de tableau
        $documentsArray = $documentsManquants->toArray();

        // Séparer les documents actifs et historiques
        $actifs = [];
        $historique = [];

        foreach ($documentsArray as $doc) {
            if ($doc->getDateReception()) {
                $historique[] = $doc;
            } else {
                $actifs[] = $doc;
            }
        }

        // Trier par ID décroissant (plus récent en premier)
        // Note : tri en PHP car les documents sont déjà chargés en mémoire
        // Alternative : utiliser ORDER BY dans une requête DQL custom si performance critique
        usort($actifs, fn ($a, $b): int => $b->getId() <=> $a->getId());

        usort($historique, fn ($a, $b): int => $b->getId() <=> $a->getId());

        // Reconstruire la collection : actifs d'abord, puis historique
        $documentsManquants->clear();

        foreach ($actifs as $doc) {
            $documentsManquants->add($doc);
        }

        foreach ($historique as $doc) {
            $documentsManquants->add($doc);
        }
    }
}
