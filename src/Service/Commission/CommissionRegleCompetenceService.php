<?php

namespace App\Service\Commission;

use App\Constant\GenreEtablissement;
use App\Entity\Commission;
use App\Entity\CommissionRegle;
use App\Entity\CommissionRegleLocalSommeil;
use App\Entity\Commune;
use App\Entity\EtablissementCategorie;
use App\Entity\EtablissementType;
use App\Entity\Groupement;
use App\Entity\IghClasse;

/**
 * Applique les règles de compétence d'une commission pour déterminer
 * quels établissements doivent lui être rattachés.
 *
 * Deux logiques distinctes selon le genre de l'établissement :
 *  - ERP  (ID_GENRE = 2) : commune + local sommeil + type + catégorie
 *  - IGH  (ID_GENRE = 5) : commune + classe
 */
final class CommissionRegleCompetenceService
{
    /**
     * Retourne les IDs des établissements correspondant à au moins une règle de compétence.
     *
     * @param array<int, array{ID_ETABLISSEMENTINFORMATIONS: string, ID_GENRE: string, NUMINSEE_COMMUNE: string, LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS: bool|null, ID_TYPE: string|null, ID_CATEGORIE: string|null, ID_CLASSE: string|null}> $etablissements
     *
     * @return int[]
     */
    public function findMatchingEtablissementIds(Commission $commission, array $etablissements): array
    {
        $regles = $commission->getRegles()->toArray();

        if ([] === $regles) {
            return [];
        }

        $ids = [];

        foreach ($etablissements as $ets) {
            foreach ($regles as $regle) {
                if (
                    (GenreEtablissement::ERP === (int) $ets['ID_GENRE'] && $this->matchesErp($ets, $regle))
                    || (GenreEtablissement::IGH === (int) $ets['ID_GENRE'] && $this->matchesIgh($ets, $regle))
                ) {
                    $ids[] = (int) $ets['ID_ETABLISSEMENTINFORMATIONS'];
                    break;
                }
            }
        }

        return $ids;
    }

    /**
     * Vérifie si un établissement ERP correspond à une règle de compétence.
     * Critères : commune, local sommeil, type d'activité, catégorie.
     *
     * Si une collection est vide, la règle est écartée car cela n'a fonctionnellement pas de sens.
     * Si l'on veut faire une comparaison sur l'entiereté du critère, on coche dans ce cas toutes les valeurs disponibles.
     *
     * @param array{ID_ETABLISSEMENTINFORMATIONS: string, ID_GENRE: string, NUMINSEE_COMMUNE: string, LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS: bool|null, ID_TYPE: string|null, ID_CATEGORIE: string|null, ID_CLASSE: string|null} $ets
     */
    private function matchesErp(array $ets, CommissionRegle $regle): bool
    {
        if (!$this->communeMatches($ets['NUMINSEE_COMMUNE'], $regle)) {
            return false;
        }

        $localSommeilValues = array_map(
            static fn (CommissionRegleLocalSommeil $ls): bool => (bool) $ls->getLocalSommeil(),
            $regle->getLocalSommeil()->toArray()
        );
        if (!in_array($ets['LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS'], $localSommeilValues, true)) {
            return false;
        }

        $typeIds = array_map(static fn (EtablissementType $t): ?int => $t->getId(), $regle->getTypes()->toArray());
        if (!in_array((int) $ets['ID_TYPE'], $typeIds, true)) {
            return false;
        }

        $categoryIds = array_map(static fn (EtablissementCategorie $c): ?int => $c->getId(), $regle->getCategories()->toArray());

        return in_array((int) $ets['ID_CATEGORIE'], $categoryIds, true);
    }

    /**
     * Vérifie si un établissement IGH correspond à une règle de compétence.
     * Critères : commune, classe IGH.
     *
     * @param array{ID_ETABLISSEMENTINFORMATIONS: string, ID_GENRE: string, NUMINSEE_COMMUNE: string, LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS: bool|null, ID_TYPE: string|null, ID_CATEGORIE: string|null, ID_CLASSE: string|null} $ets
     */
    private function matchesIgh(array $ets, CommissionRegle $regle): bool
    {
        if (!$this->communeMatches($ets['NUMINSEE_COMMUNE'], $regle)) {
            return false;
        }

        $classeIds = array_map(static fn (IghClasse $cl): ?int => $cl->getId(), $regle->getClasses()->toArray());

        return in_array((int) $ets['ID_CLASSE'], $classeIds, true);
    }

    /**
     * Vérifie si le code INSEE de l'établissement est couvert par la règle
     * (via commune directe ou via les communes du groupement associé).
     */
    private function communeMatches(string $numInseeEts, CommissionRegle $regle): bool
    {
        if ($regle->getCommune() instanceof Commune) {
            return $numInseeEts === $regle->getCommune()->getId();
        }

        if ($regle->getGroupement() instanceof Groupement) {
            foreach ($regle->getGroupement()->getCommunes() as $commune) {
                if ($numInseeEts === $commune->getId()) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }
}
