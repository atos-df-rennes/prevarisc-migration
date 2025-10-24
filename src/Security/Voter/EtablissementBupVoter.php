<?php

namespace App\Security\Voter;

use App\Entity\Etablissement;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Service\Voter\VoterUtils;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EtablissementBupVoter extends EtablissementVoter
{
    private const ID_BUP = 6;

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
        return parent::supports($attribute, $subject) && parent::isSubjectGenreAllowed($subject, self::ID_BUP);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool {
        $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissement(self::ID_BUP);
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
        $resourcesByParam = $this->indexResourcesByParam($resources);

        if ($this->hasAccessToAll($attribute, $resourcesByParam, $userPrivileges)) {
            return true;
        }

        return $this->checkSpecificRules($attribute, $resourcesByParam, $userPrivileges, $user, $subject);
    }

    /**
     * @param \App\Entity\Resources[] $resources
     *
     * @return array<int, array<int, \App\Entity\Resources>>
     */
    private function indexResourcesByParam(array $resources): array {
        $grouped = [];
        foreach ($resources as $resource) {
            $name = $resource->getName();
            if (!$name) {
                continue;
            }

            $parts = explode('_', $name);
            if (count($parts) === 4) {
                $useUserGroupements = (int) $parts[2];
                $useUserCommune = (int) $parts[3];
                $grouped[$useUserGroupements][$useUserCommune] = $resource;
            }
        }
        return $grouped;
    }

    /**
     * @param array<int, array<int, \App\Entity\Resources>> $resourcesByParam
     */
    private function hasAccessToAll(string $attribute, array $resourcesByParam, Collection $userPrivileges): bool {
        if (isset($resourcesByParam[0][0])) {
            $privilege = $this->privilegesRepository->findOneBy([
                'name' => $attribute,
                'resource' => $resourcesByParam[0][0]
            ]);
            return $privilege && $userPrivileges->contains($privilege);
        }
        return false;
    }

    /**
     * @param array<int, array<int, \App\Entity\Resources>> $resourcesByParam
     */
    private function checkSpecificRules(string $attribute, array $resourcesByParam, Collection $userPrivileges, Utilisateur $user, Etablissement $subject): bool {
        foreach ($resourcesByParam as $useUserGroupements => $subArray) {
            foreach ($subArray as $useUserCommune => $resource) {
                $privilege = $this->privilegesRepository->findOneBy([
                    'name' => $attribute,
                    'resource' => $resource
                ]);
                if ($privilege && $userPrivileges->contains($privilege)) {
                    return $this->voterUtils->isUserGroupementsAndCommuneAllowed($user, $subject, $useUserGroupements, $useUserCommune);
                }
            }
        }
        return false;
    }
}
