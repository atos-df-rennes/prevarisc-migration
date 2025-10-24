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

            // Nom attendu : etablissement_igh_{classe(-classe)*}_{param1}_{param2}
            $parts = explode('_', $name);

            if (5 !== count($parts)) {
                continue;
            }

            $classesStr = explode('-', $parts[2]);

            foreach ($classesStr as $classeStr) {
                $classe = (int) $classeStr;
                $useUserGroupements = (int) $parts[3];
                $useUserCommune = (int) $parts[4];

                $resourcesByParam[$classe][$useUserGroupements][$useUserCommune][] = $resource;
            }
        }

        // Si une des règles concernant "toutes les classes" permet de donner l'accès, on return directement.
        if (array_key_exists(0, $resourcesByParam)) {
            foreach ($resourcesByParam[0] as $useUserGroupements => $subArray) {
                foreach (array_keys($subArray) as $useUserCommune) {
                    $privilege = $this->privilegesRepository->findOneBy(array(
                        'name' => $attribute,
                        'resource' => $resourcesByParam[0][$useUserGroupements][$useUserCommune][0]
                    ));
                    if (
                        $privilege
                        && $userPrivileges->contains($privilege)
                        && $this->voterUtils->isUserGroupementsAndCommuneAllowed($user, $subject, $useUserGroupements, $useUserCommune)
                    ) {
                        return true;
                    }
                }
            }
        }

        $ighClasse = $subject->getLatestInformations()->getClasse();
        if (null === $ighClasse) {
            return false;
        }

        $ighClasseId = $ighClasse->getId();
        if (null === $ighClasseId) {
            return false;
        }

        // Règles spécifiques
        if (array_key_exists($ighClasseId, $resourcesByParam)) {
            foreach ($resourcesByParam[$ighClasseId] as $useUserGroupements => $subSubArray) {
                foreach ($subSubArray as $useUserCommune => $resources) {
                    foreach ($resources as $resource) {
                        $privilege = $this->privilegesRepository->findOneBy(array(
                            'name' => $attribute,
                            'resource' => $resource
                        ));
                        if (
                            $privilege
                            && $userPrivileges->contains($privilege)
                            && $this->voterUtils->isUserGroupementsAndCommuneAllowed($user, $subject, $useUserGroupements, $useUserCommune)
                        ) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}

