<?php

namespace App\Service\Voter;

use App\Entity\Etablissement;
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
}
