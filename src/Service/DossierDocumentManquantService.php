<?php

namespace App\Service;

use App\Entity\Dossier;
use Doctrine\Common\Collections\Collection;

/**
 * Service gérant la logique métier des documents manquants.
 */
class DossierDocumentManquantService
{
    /**
     * Organise les documents manquants d'un dossier par ordre décroissant.
     * Sépare les documents actifs (sans date réception) des documents historiques (avec date réception).
     *
     * @param Dossier $dossier Le dossier contenant les documents
     *
     * @return void
     */
    public function organizeDocumentsManquants(Dossier $dossier): void
    {
        $documentsManquants = $dossier->getDocumentsManquants();

        if ($documentsManquants->count() === 0) {
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
        usort($actifs, function ($a, $b) {
            return $b->getId() <=> $a->getId();
        });

        usort($historique, function ($a, $b) {
            return $b->getId() <=> $a->getId();
        });

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
