<?php

namespace App\Security\Voter;

use App\Entity\Etablissement;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Service\Voter\VoterUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EtablissementHabitationVoter extends EtablissementVoter
{
    private const ID_HABITATION = 4;

    /**
     * @var ResourcesRepository
     */
    private $resourcesRepository;

    /**
     * @var PrivilegesRepository
     */
    private $privilegesRepository;

    /**
     * @var VoterUtils
     */
    private $voterUtils;

    public function __construct(ResourcesRepository $resourcesRepository, PrivilegesRepository $privilegesRepository, VoterUtils $voterUtils)
    {
        $this->resourcesRepository = $resourcesRepository;
        $this->privilegesRepository = $privilegesRepository;
        $this->voterUtils = $voterUtils;
    }

    protected function supports($attribute, $subject): bool
    {
        if (!parent::supports($attribute, $subject)) {
            return false;
        }

        return parent::isSubjectGenreAllowed($subject, self::ID_HABITATION);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissement(self::ID_HABITATION);
        if ([] === $resources) {
            return false;
        }

        $user = $token->getUser();
        if (!$user instanceof Utilisateur) {
            return false;
        }

        if (!$subject instanceof Etablissement) {
            return false;
        }

        $userGroup = $user->getGroupe();
        if (null === $userGroup) {
            return false;
        }

        $userPrivileges = $userGroup->getPrivileges();

        // Indexer les ressources par paramètre
        $resourcesByParam = [];
        foreach ($resources as $resource) {
            $name = $resource->getName();
            if (!$name) {
                continue;
            }

            // Nom attendu : etablissement_habitation_{famille(-famille)*}_{param1}_{param2}
            $parts = explode('_', $name);
            if (5 !== count($parts)) {
                continue;
            }

            $famillesStr = explode('-', $parts[2]);

            foreach ($famillesStr as $familleStr) {
                $famille = (int) $familleStr;
                $useUserGroupements = (int) $parts[3];
                $useUserCommune = (int) $parts[4];
                $resourcesByParam[$famille][$useUserGroupements][$useUserCommune][] = $resource;
            }
        }

        /*
         * Si une des règles concernant "toutes les familles" permet de donner l'accès, on return directement.
         */
        if (array_key_exists(0, $resourcesByParam)) {
            $allFamillesCheck = false;

            foreach ($resourcesByParam[0] as $useUserGroupements => $subArray) {
                foreach ($subArray as $useUserCommune => $resource) {
                    $privilege = $this->privilegesRepository->findOneBy([
                        'name' => $attribute,
                        'resource' => $resourcesByParam[0][$useUserGroupements][$useUserCommune][0]
                    ]);

                    if (
                        $privilege
                        && $userPrivileges->contains($privilege)
                        && $this->voterUtils->isUserGroupementsAndCommuneAllowed($user, $subject, $useUserGroupements, $useUserCommune)
                    ) {
                        $allFamillesCheck = true;

                        break 2;
                    }
                }
            }

            if ($allFamillesCheck) {
                return true;
            }
        }

        $habitationFamille = $subject->getLatestInformations()->getFamille();

        if (null === $habitationFamille) {
            return false;
        }

        $habitationFamilleId = $habitationFamille->getId();

        if (null === $habitationFamilleId) {
            return false;
        }

        // Règles spécifiques
        if (array_key_exists($habitationFamilleId, $resourcesByParam)) {
            $familleCheck = false;

            foreach ($resourcesByParam[$habitationFamilleId] as $useUserGroupements => $subSubArray) {
                foreach ($subSubArray as $useUserCommune => $resources) {
                    foreach ($resources as $resource) {
                        $privilege = $this->privilegesRepository->findOneBy([
                            'name' => $attribute,
                            'resource' => $resource
                        ]);
                        if (
                            $privilege
                            && $userPrivileges->contains($privilege)
                            && $this->voterUtils->isUserGroupementsAndCommuneAllowed($user, $subject, $useUserGroupements, $useUserCommune)
                        ) {
                            $familleCheck = true;

                            break 3;
                        }
                    }
                }
            }

            return $familleCheck;
        }

        return false;
    }
}
