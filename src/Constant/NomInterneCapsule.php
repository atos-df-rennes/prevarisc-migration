<?php

namespace App\Constant;

/**
 * Constantes pour les noms internes des capsules de rubriques.
 *
 * Correspond à la colonne `NOM_INTERNE` de la table `capsulerubrique` en base de données.
 */
final class NomInterneCapsule
{
    public const DESCRIPTIF_ETABLISSEMENT = 'descriptifEtablissement';

    public const DESCRIPTIF_VERIFICATIONS_TECHNIQUES = 'descriptifVerificationsTechniques';

    public const EFFECTIFS_DEGAGEMENTS_ETABLISSEMENT = 'effectifsDegagementsEtablissement';

    public const EFFECTIFS_DEGAGEMENTS_DOSSIER = 'effectifsDegagementsDossier';

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
