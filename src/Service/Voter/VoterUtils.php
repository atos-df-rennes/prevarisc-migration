<?php

namespace App\Service\Voter;

use App\Entity\Etablissement;
use App\Entity\EtablissementActivite;
use App\Entity\EtablissementCategorie;
use App\Entity\Utilisateur;

class VoterUtils
{
    public function isUserGroupementsAndCommuneAllowed(Utilisateur $user, Etablissement $subject, int $useUserGroupements, int $useUserCommune): bool
    {
        $checkGroupements = true;
        $checkCommune = true;

        if (1 === $useUserGroupements) {
            $checkGroupements = false;

            $userGroupements = $user->getGroupements();
            $bupAdresses = $subject->getAdresses();

            foreach ($userGroupements as $userGroupement) {
                foreach ($bupAdresses as $bupAdresse) {
                    $commune = $bupAdresse->getCommune();

                    if ($userGroupement->getCommunes()->contains($commune)) {
                        $checkGroupements = true;
                        break 2;
                    }
                }
            }
        }

        if (1 === $useUserCommune) {
            $checkCommune = false;

            $userCommune = $user->getCommune();
            $bupAdresses = $subject->getAdresses();

            foreach ($bupAdresses as $bupAdresse) {
                $commune = $bupAdresse->getCommune();

                if ($commune === $userCommune) {
                    $checkCommune = true;
                    break;
                }
            }
        }

        return $checkGroupements && $checkCommune;
    }

    public function isUserCommissionsAllowed(Utilisateur $user, Etablissement $subject, int $useUserCommissions): bool
    {
        if (0 === $useUserCommissions) {
            return true;
        }

        $userCommissions = $user->getCommissions();
        $etablissementCommission = $subject->getLatestInformations()
            ->getCommission();

        return $userCommissions->contains($etablissementCommission);
    }

    /**
     * @param int[] $typesActivites
     * @param int[] $categories
     */
    public function isTypeActiviteAndCategorieAllowed(Etablissement $subject, array $typesActivites, array $categories): bool
    {
        $checkTypesActivites = false;
        $checkCategories = false;

        $erpTypeActivite = $subject->getLatestInformations()->getTypeActivite();

        $erpTypeActiviteId = null;
        if ($erpTypeActivite instanceof EtablissementActivite) {
            $erpTypeActiviteId = $erpTypeActivite->getId();
        }

        $erpCategorie = $subject->getCategorieEffective();

        $erpCategorieId = null;
        if ($erpCategorie instanceof EtablissementCategorie) {
            $erpCategorieId = $erpCategorie->getId();
        }

        if (
            in_array(0, $typesActivites, true)
            || in_array($erpTypeActiviteId, $typesActivites, true)
        ) {
            $checkTypesActivites = true;
        }

        if (
            in_array(0, $categories, true)
            || in_array($erpCategorieId, $categories, true)
        ) {
            $checkCategories = true;
        }

        return $checkTypesActivites && $checkCategories;
    }
}
