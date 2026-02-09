<?php

namespace App\Service\Alerte;

use App\Entity\Etablissement;
use App\Repository\DossierRepository;

/**
 * Service de génération d'objet email pour les alertes.
 *
 * Équivalent legacy : Service_Changement::getObjet() + getAvis()
 */
class ObjetService
{
    /** @var DossierRepository */
    private $dossierRepository;

    public function __construct(DossierRepository $dossierRepository)
    {
        $this->dossierRepository = $dossierRepository;
    }

    /**
     * Génère l'objet de l'email d'alerte.
     *
     * Équivalent legacy : Service_Changement::getObjet()
     *
     * Format : "{Libellé établissement} ({Commune}) - {Type changement}"
     * Exemples :
     * - "Mairie de Gravigny (Gravigny) - Passage en avis Favorable"
     * - "Complexe A Boutique B (Évreux) - Passage au statut Ouvert"
     *
     * @param int           $typeChangement Type de changement (1=statut, 2=avis, 3=classement)
     * @param Etablissement $etablissement  Établissement concerné
     *
     * @return string Objet email formaté
     */
    public function generer(int $typeChangement, Etablissement $etablissement): string
    {
        // 1. Partie changement selon type
        $partieChangement = $this->getPartieChangement($typeChangement, $etablissement);

        // 2. Partie établissement : libellé + parent si existe
        $latestInfos = $etablissement->getLatestInformations();
        $libelleEtablissement = $latestInfos->getLibelle() ?? 'Établissement';

        // Ajouter parent direct si existe
        $parent = $etablissement->getParent();
        if ($parent) {
            $parentInfos = $parent->getLatestInformations();
            $parentLibelle = $parentInfos->getLibelle() ?? '';
            if ('' !== $parentLibelle) {
                $libelleEtablissement = sprintf(
                    '%s %s',
                    $parentLibelle,
                    $libelleEtablissement
                );
            }
        }

        // 3. Partie commune
        $commune = '';
        $adresses = $etablissement->getAdresses();
        if (!$adresses->isEmpty()) {
            /** @var \App\Entity\EtablissementAdresse|false $premiereAdresse */
            $premiereAdresse = $adresses->first();
            if (false !== $premiereAdresse) {
                $communeEntity = $premiereAdresse->getCommune();
                if ($communeEntity) {
                    $commune = $communeEntity->getLibelle() ?? '';
                }
            }
        }

        // 4. Format final
        return sprintf(
            '%s (%s) - %s',
            $libelleEtablissement,
            $commune,
            $partieChangement
        );
    }

    /**
     * Génère la partie "changement" de l'objet email selon le type.
     *
     * @param int           $typeChangement Type de changement
     * @param Etablissement $etablissement  Établissement concerné
     *
     * @return string Partie changement formatée
     */
    private function getPartieChangement(int $typeChangement, Etablissement $etablissement): string
    {
        switch ($typeChangement) {
            case AlerteService::TYPE_CHANGEMENT_STATUT:
                return $this->getObjetStatut($etablissement);

            case AlerteService::TYPE_CHANGEMENT_AVIS:
                return $this->getObjetAvis($etablissement);

            case AlerteService::TYPE_CHANGEMENT_CLASSEMENT:
                return $this->getObjetClassement($etablissement);

            default:
                return 'Changement';
        }
    }

    /**
     * Génère objet pour changement statut.
     *
     * Format : "Passage au statut {Libellé statut}"
     *
     * @param Etablissement $etablissement Établissement concerné
     *
     * @return string Objet formaté
     */
    private function getObjetStatut(Etablissement $etablissement): string
    {
        $latestInfos = $etablissement->getLatestInformations();
        $statut = $latestInfos->getStatut();
        $libelleStatut = ($statut ? $statut->getLibelle() : null) ?? 'Inconnu';

        return sprintf('Passage au statut "%s"', $libelleStatut);
    }

    /**
     * Génère objet pour changement avis.
     *
     * Format : "Passage en avis {Favorable|Défavorable|...}"
     *
     * @param Etablissement $etablissement Établissement concerné
     *
     * @return string Objet formaté
     */
    private function getObjetAvis(Etablissement $etablissement): string
    {
        $avisTexte = $this->getAvisTexte($etablissement);

        return sprintf('Passage en avis "%s"', $avisTexte);
    }

    /**
     * Génère objet pour changement classement.
     *
     * Format : "Changement de classement {Catégorie} - {Type} {TypeActivité}"
     *
     * @param Etablissement $etablissement Établissement concerné
     *
     * @return string Objet formaté
     */
    private function getObjetClassement(Etablissement $etablissement): string
    {
        $latestInfos = $etablissement->getLatestInformations();

        $categorie = $latestInfos->getCategorie();
        $type = $latestInfos->getType();
        $typeActivite = $latestInfos->getTypeActivite();

        $libelleCategorie = ($categorie ? $categorie->getLibelle() : null) ?? '';
        $libelleType = ($type ? $type->getLibelle() : null) ?? '';
        $libelleTypeActivite = ($typeActivite ? $typeActivite->getLibelle() : null) ?? '';

        return sprintf(
            'Changement de classement "%s - %s %s"',
            $libelleCategorie,
            $libelleType,
            $libelleTypeActivite
        );
    }

    /**
     * Récupère le texte d'avis de l'établissement.
     *
     * Équivalent legacy : Service_Changement::getAvis() + Service_Etablissement::getAvisEtablissement()
     *
     * Logique :
     * 1. Cherche dernier dossier avec avis différé
     * 2. Si existe ET plus récent que dossier donnant avis → "Présence d'un dossier avec avis differé"
     * 3. Sinon récupère avis du dossier donnant avis
     * 4. Ajoute " à l'exploitation" sauf pour genre Cellule
     *
     * @param Etablissement $etablissement Établissement concerné
     *
     * @return string Texte avis
     */
    private function getAvisTexte(Etablissement $etablissement): string
    {
        $dossierDonnantAvis = $etablissement->getDossierDonnantAvis();

        if (!$dossierDonnantAvis) {
            return "Avis d'exploitation indisponible";
        }

        // 1. Chercher dernier dossier avec avis différé
        $dossierAvisDiffere = $this->dossierRepository->findLastDossierAvisDiffere($etablissement);

        // 2. Si dossier différé existe et est plus récent que dossier donnant avis
        if ($dossierAvisDiffere) {
            $dateInsertDonnantAvis = $dossierDonnantAvis->getDateInsert();
            $dateInsertDiffere = $dossierAvisDiffere->getDateInsert();

            // Compare : si différé plus récent (ou égal) → avis différé prioritaire
            if ($dateInsertDiffere >= $dateInsertDonnantAvis) {
                return "Présence d'un dossier avec avis differé";
            }
        }

        // 3. Sinon, avis du dossier donnant avis
        $avisCommission = $dossierDonnantAvis->getAvisCommission();

        if (!$avisCommission) {
            return "Avis d'exploitation indisponible";
        }

        $latestInfos = $etablissement->getLatestInformations();
        $genre = $latestInfos->getGenre();
        $isCellule = $genre && 2 === $genre->getId(); // ID_GENRE_CELLULE = 2

        $avisId = $avisCommission->getId();

        if (1 === $avisId) {
            return 'Favorable'.($isCellule ? '' : " à l'exploitation");
        }

        if (2 === $avisId) {
            return 'Défavorable'.($isCellule ? '' : " à l'exploitation");
        }

        $libelle = $avisCommission->getLibelle();

        return $libelle ?? "Avis d'exploitation indisponible";
    }
}
