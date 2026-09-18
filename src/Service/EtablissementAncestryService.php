<?php

namespace App\Service;

use App\Entity\Etablissement;

/**
 * Résout la chaîne d'ancêtres (parents, grands-parents, ...) d'un établissement.
 *
 * Port de l'algorithme dupliqué historiquement dans EtablissementLateralNavigationSubscriber
 * (fil d'Ariane) et dans l'API d'aperçu établissement (bulle popover) : climb via getParent()
 * jusqu'à la racine, puis inversion pour obtenir un ordre "racine en premier".
 */
class EtablissementAncestryService
{
    /**
     * @return Etablissement[] Ancêtres de l'établissement, racine en premier, parent direct en dernier.
     *                         Tableau vide si l'établissement n'a pas de parent.
     */
    public function getAncetres(Etablissement $etablissement): array
    {
        $ancetres = [];
        $courant = $etablissement->getParent();

        while (null !== $courant) {
            $ancetres[] = $courant;
            $courant = $courant->getParent();
        }

        return array_reverse($ancetres);
    }
}
