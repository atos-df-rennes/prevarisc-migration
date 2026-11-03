<?php

namespace App\Service\Alerte;

use App\Constant\TypeChangement;
use App\Entity\Etablissement;
use App\Entity\EtablissementActivite;
use App\Entity\EtablissementAdresse;
use App\Entity\EtablissementCategorie;
use App\Entity\EtablissementStatut;
use App\Entity\EtablissementType;

/**
 * Service de génération d'objet email pour les alertes.
 *
 * Équivalent legacy : Service_Changement::getObjet()
 */
final readonly class ObjetService
{
    /**
     * @param AvisService $avisService Service utilitaire pour récupération avis
     */
    public function __construct(private AvisService $avisService)
    {
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
        if ($parent instanceof Etablissement) {
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
            /** @var EtablissementAdresse|false $premiereAdresse */
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
        return match ($typeChangement) {
            TypeChangement::STATUT => $this->getObjetStatut($etablissement),
            TypeChangement::AVIS => $this->getObjetAvis($etablissement),
            TypeChangement::CLASSEMENT => $this->getObjetClassement($etablissement),
            default => 'Changement',
        };
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
        $libelleStatut = ($statut instanceof EtablissementStatut ? $statut->getLibelle() : null) ?? 'Inconnu';

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
        $avisTexte = $this->avisService->getTexte($etablissement);

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

        $libelleCategorie = ($categorie instanceof EtablissementCategorie ? $categorie->getLibelle() : null) ?? '';
        $libelleType = ($type instanceof EtablissementType ? $type->getLibelle() : null) ?? '';
        $libelleTypeActivite = ($typeActivite instanceof EtablissementActivite ? $typeActivite->getLibelle() : null) ?? '';

        return sprintf(
            'Changement de classement "%s - %s %s"',
            $libelleCategorie,
            $libelleType,
            $libelleTypeActivite
        );
    }
}
