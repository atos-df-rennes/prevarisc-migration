<?php

declare(strict_types=1);

namespace App\Service\Alerte;

use App\Constant\GenreEtablissement;
use App\Entity\Etablissement;
use App\Repository\DossierRepository;

/**
 * Service utilitaire pour la récupération du texte d'avis d'un établissement.
 *
 * Logique extraite de Service_Changement::getAvis() pour réutilisation.
 * Utilisé par ObjetService (objet email) et MessageService (balise message).
 */
class AvisService
{
    private DossierRepository $dossierRepository;

    /**
     * @param DossierRepository $dossierRepository Pour vérifier avis différé
     */
    public function __construct(DossierRepository $dossierRepository)
    {
        $this->dossierRepository = $dossierRepository;
    }

    /**
     * Récupère le texte d'avis formaté pour un établissement.
     *
     * Équivalent legacy : Service_Changement::getAvis()
     *
     * LOGIQUE AVIS :
     * 1. Si dossier avec avis différé + date insertion >= dossier donnant avis
     *    → "Présence d'un dossier avec avis differé"
     * 2. Sinon si avis disponible dans dossier donnant avis
     *    → "Favorable [à l'exploitation]" ou "Défavorable [à l'exploitation]"
     *    (pas de suffixe pour genre Cellule)
     * 3. Sinon → "Avis d'exploitation indisponible"
     *
     * @param Etablissement $etablissement Établissement concerné
     *
     * @return string Texte d'avis formaté
     */
    public function getTexte(Etablissement $etablissement): string
    {
        $latestInfos = $etablissement->getLatestInformations();
        $dossierDonnantAvis = $etablissement->getDossierDonnantAvis();

        // Cas 1 : Vérifier présence avis différé plus récent
        $dossierAvisDiffere = $this->dossierRepository->findLastDossierAvisDiffere($etablissement);
        if (null !== $dossierAvisDiffere && null !== $dossierDonnantAvis) {
            $dateAvisDiffere = $dossierAvisDiffere->getDateInsert();
            $dateDossierDonnantAvis = $dossierDonnantAvis->getDateInsert();

            // Si avis différé >= dossier donnant avis → priorité avis différé
            if ($dateAvisDiffere >= $dateDossierDonnantAvis) {
                return "Présence d'un dossier avec avis differé";
            }
        }

        // Cas 2 : Avis du dossier donnant avis
        if (null !== $dossierDonnantAvis) {
            $avis = $dossierDonnantAvis->getAvisCommission();
            if (null !== $avis) {
                $libelleAvis = $avis->getLibelle();
                $texteAvis = is_string($libelleAvis) ? $libelleAvis : '';

                // Pas de suffixe pour genre Cellule
                $genre = $latestInfos->getGenre();
                $ajouterSuffixe = null === $genre || GenreEtablissement::CELLULE !== $genre->getId();

                if ($ajouterSuffixe && '' !== $texteAvis) {
                    return $texteAvis." à l'exploitation";
                }

                return $texteAvis;
            }
        }

        // Cas 3 : Aucun avis disponible
        return "Avis d'exploitation indisponible";
    }
}
