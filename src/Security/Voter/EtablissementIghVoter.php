<?php

namespace App\Security\Voter;

use App\Entity\Etablissement;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Service\Voter\VoterUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EtablissementIghVoter extends EtablissementVoter
{
    private const ID_IGH = 5;

    /** @var \App\Repository\ResourcesRepository */
    private $resourcesRepository;

    /** @var \App\Repository\PrivilegesRepository */
    private $privilegesRepository;

    /** @var \App\Service\Voter\VoterUtils */
    private $voterUtils;

    public function __construct(ResourcesRepository $resourcesRepository, PrivilegesRepository $privilegesRepository, VoterUtils $voterUtils)
    {
        $this->resourcesRepository = $resourcesRepository;
        $this->privilegesRepository = $privilegesRepository;
        $this->voterUtils = $voterUtils;
    }

    protected function supports($attribute, $subject): bool {
        return parent::supports($attribute, $subject) && parent::isSubjectGenreAllowed($subject, self::ID_IGH);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool {
        $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissement(self::ID_IGH);
        if ([] === $resources) {
            return false;
        }

        $user = $token->getUser();
        if (!$user instanceof Utilisateur || !$subject instanceof Etablissement) {
            return false;
        }

        $userGroupe = $user->getGroupe();
        if (null === $userGroupe) {
            return false;
        }

        $userPrivileges = $userGroupe->getPrivileges();
        $resourcesByParam = [];
        foreach ($resources as $resource) {
            $name = $resource->getName();
            if (!$name) {
                continue;
            }
            // Nom attendu : etablissement_igh_{classe(-classe)*}_{commissionUtilisateur}_{groupementsUtilisateur}_{communeUtilisateur}
            $parts = explode('_', $name);
            if (6 !== count($parts)) {
                continue;
            }
            $classesStr = explode('-', $parts[2]);
            $commissionParam = (int) $parts[3];
            $useUserGroupements = (int) $parts[4];
            $useUserCommune = (int) $parts[5];
            foreach ($classesStr as $classeStr) {
                $classe = (int) $classeStr;
                $resourcesByParam[$classe][$commissionParam][$useUserGroupements][$useUserCommune][] = $resource;
            }
        }

        // Vérification des règles pour toutes les classes (classe=0)
        if (isset($resourcesByParam[0])) {
            foreach ($resourcesByParam[0] as $useUserCommissions => $byGroupement) {
                foreach ($byGroupement as $useUserGroupements => $byCommune) {
                    foreach ($byCommune as $useUserCommune => $resourcesList) {
                        $privilege = $this->privilegesRepository->findOneBy([
                            'name' => $attribute,
                            'resource' => $resourcesList[0]
                        ]);
                        if ($privilege && $userPrivileges->contains($privilege)) {
                            if (
                                $this->voterUtils->isUserGroupementsAndCommuneAllowed($user, $subject, $useUserGroupements, $useUserCommune)
                                && $this->voterUtils->isUserCommissionsAllowed($user, $subject, $useUserCommissions)
                            ) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        $latestInfos = $subject->getLatestInformations();
        $ighClasse = $latestInfos->getClasse();
        if (null === $ighClasse) {
            return false;
        }

        $ighClasseId = $ighClasse->getId();
        if (null === $ighClasseId) {
            return false;
        }

        // Vérification des règles spécifiques à la classe
        if (isset($resourcesByParam[$ighClasseId])) {
            foreach ($resourcesByParam[$ighClasseId] as $useUserCommissions => $byGroupement) {
                foreach ($byGroupement as $useUserGroupements => $byCommune) {
                    foreach ($byCommune as $useUserCommune => $resourcesList) {
                        foreach ($resourcesList as $resource) {
                            $privilege = $this->privilegesRepository->findOneBy([
                                'name' => $attribute,
                                'resource' => $resource
                            ]);
                            if ($privilege && $userPrivileges->contains($privilege)) {
                                if (
                                    $this->voterUtils->isUserGroupementsAndCommuneAllowed($user, $subject, $useUserGroupements, $useUserCommune)
                                    && $this->voterUtils->isUserCommissionsAllowed($user, $subject, $useUserCommissions)
                                ) {
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        }
        return false;
    }
}
