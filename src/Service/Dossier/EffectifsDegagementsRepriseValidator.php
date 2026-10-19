<?php

namespace App\Service\Dossier;

use App\Constant\DossierType;
use App\Entity\Dossier;

/**
 * Valide qu'un dossier peut être utilisé comme source lors de la reprise
 * des effectifs et dégagements vers un dossier cible.
 */
final class EffectifsDegagementsRepriseValidator
{
    /**
     * Retourne null si le dossier source est valide, ou un message d'erreur sinon.
     *
     * Règles vérifiées :
     *  - le dossier source est différent du dossier cible
     *  - le dossier source est de type "étude"
     *  - le dossier source appartient au même établissement que le dossier cible
     */
    public function validerSource(Dossier $cible, Dossier $source): ?string
    {
        if ($source->getId() === $cible->getId()) {
            return 'Le dossier source ne peut pas être le dossier courant.';
        }

        $type = $source->getType();
        if (null === $type || DossierType::ETUDE !== $type->getId()) {
            return 'Le dossier source doit être une étude.';
        }

        $etablissementsCible = $cible->getEtablissements();
        if (1 !== $etablissementsCible->count()) {
            return 'Le dossier courant doit être lié à un seul établissement.';
        }

        $etablissementCible = $etablissementsCible->first();
        if (false === $etablissementCible) {
            return 'Le dossier courant doit être lié à un seul établissement.';
        }

        foreach ($source->getEtablissements() as $etablissementSource) {
            if ($etablissementSource->getId() === $etablissementCible->getId()) {
                return null;
            }
        }

        return 'Le dossier source n\'appartient pas au même établissement que le dossier courant.';
    }
}
