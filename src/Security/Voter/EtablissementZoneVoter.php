<?php

namespace App\Security\Voter;

use App\Entity\Etablissement;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EtablissementZoneVoter extends EtablissementVoter
{
    private const ID_ZONE = 10;

    /**
     * @var \App\Repository\ResourcesRepository
     */
    private $resourcesRepository;

    /**
     * @var \App\Repository\PrivilegesRepository
     */
    private $privilegesRepository;

    public function __construct(ResourcesRepository $resourcesRepository, PrivilegesRepository $privilegesRepository)
    {
        $this->resourcesRepository = $resourcesRepository;
        $this->privilegesRepository = $privilegesRepository;
    }

    protected function supports($attribute, $subject): bool
    {
        return parent::supports($attribute, $subject) &&
            parent::isSubjectGenreAllowed($subject, self::ID_ZONE);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissement(self::ID_ZONE);
        if ([] === $resources || !$subject instanceof Etablissement) {
            return false;
        }

        $user = $token->getUser();
        if (!$user instanceof Utilisateur) {
            return false;
        }

        $userGroupe = $user->getGroupe();
        if (null === $userGroupe) {
            return false;
        }

        $resourcesById = $this->indexResourcesById($resources);

        if ($this->hasPrivilegeForAllClasses($resourcesById, $attribute, $user)) {
            return true;
        }

        return $this->hasPrivilegeForSpecificClass($resourcesById, $attribute, $user, $subject);
    }

    private function indexResourcesById(array $resources): array
    {
        $resourcesById = [];

        foreach ($resources as $resource) {
            $name = $resource->getName();
            if (!$name) {
                continue;
            }

            $parts = explode('_', $name);
            if (count($parts) !== 5) {
                continue;
            }

            $classementIdsStr = explode('-', $parts[2]);
            foreach ($classementIdsStr as $classementIdStr) {
                $resourcesById[(int) $classementIdStr][] = $resource;
            }
        }

        return $resourcesById;
    }

    private function hasPrivilegeForAllClasses(array $resourcesById, string $attribute, Utilisateur $user): bool
    {
        if (!array_key_exists(0, $resourcesById)) {
            return false;
        }

        $privilege = $this->privilegesRepository->findOneBy([
            'name' => $attribute,
            'resource' => $resourcesById[0][0]
        ]);

        return $privilege && $user->getGroupe()->getPrivileges()->contains($privilege);
    }

    private function hasPrivilegeForSpecificClass(array $resourcesById, string $attribute, Utilisateur $user, Etablissement $subject): bool
    {
        $classement = $subject->getLatestInformations()->getClassement();
        if (null === $classement || !array_key_exists($classement->getId(), $resourcesById)) {
            return false;
        }

        foreach ($resourcesById[$classement->getId()] as $resource) {
            $privilege = $this->privilegesRepository->findOneBy([
                'name' => $attribute,
                'resource' => $resource
            ]);

            if ($privilege && $user->getGroupe()->getPrivileges()->contains($privilege)) {
                return true;
            }
        }

        return false;
    }
}
