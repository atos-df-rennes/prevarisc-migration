<?php

namespace App\Security\Voter;

use App\Entity\Etablissement;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EtablissementCelluleVoter extends EtablissementVoter
{
    private const ID_CELLULE = 3;

    /**
     * @var ResourcesRepository
     */
    private $resourcesRepository;

    /**
     * @var PrivilegesRepository
     */
    private $privilegesRepository;

    public function __construct(ResourcesRepository $resourcesRepository, PrivilegesRepository $privilegesRepository)
    {
        $this->resourcesRepository = $resourcesRepository;
        $this->privilegesRepository = $privilegesRepository;
    }

    protected function supports($attribute, $subject): bool
    {
        return parent::supports($attribute, $subject)
            && parent::isSubjectGenreAllowed($subject, self::ID_CELLULE);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissement(self::ID_CELLULE);
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

        $userPrivileges = $userGroupe->getPrivileges();
        $resourcesById = $this->indexResourcesById($resources);

        if ($this->hasPrivilegeForAllTypesActivites($resourcesById, $attribute, $userPrivileges)) {
            return true;
        }

        return $this->hasPrivilegeForSpecificTypeActivite($resourcesById, $attribute, $userPrivileges, $subject);
    }

    /**
     * @param Resources[] $resources
     *
     * @return array<int, array<int, Resources>>
     */
    private function indexResourcesById(array $resources): array
    {
        $resourcesById = [];

        foreach ($resources as $resource) {
            $name = $resource->getName();
            if (!$name) {
                continue;
            }

            $parts = explode('_', $name);
            if (4 !== count($parts)) {
                continue;
            }

            $typeActiviteIdsStr = explode('-', $parts[2]);
            foreach ($typeActiviteIdsStr as $typeActiviteIdStr) {
                $resourcesById[(int) $typeActiviteIdStr][] = $resource;
            }
        }

        return $resourcesById;
    }

    /**
     * @param array<int, array<int, Resources>> $resourcesById
     */
    private function hasPrivilegeForAllTypesActivites(array $resourcesById, string $attribute, Collection $userPrivileges): bool
    {
        if (!array_key_exists(0, $resourcesById)) {
            return false;
        }

        $privilege = $this->privilegesRepository->findOneBy([
            'name' => $attribute,
            'resource' => $resourcesById[0][0]
        ]);

        return $privilege && $userPrivileges->contains($privilege);
    }

    /**
     * @param array<int, array<int, Resources>> $resourcesById
     */
    private function hasPrivilegeForSpecificTypeActivite(array $resourcesById, string $attribute, Collection $userPrivileges, Etablissement $subject): bool
    {
        $typeActivite = $subject->getLatestInformations()->getTypeActivite();

        if (null === $typeActivite) {
            return false;
        }

        $typeActiviteId = $typeActivite->getId();

        if (null === $typeActiviteId) {
            return false;
        }

        if (!array_key_exists($typeActiviteId, $resourcesById)) {
            return false;
        }

        foreach ($resourcesById[$typeActiviteId] as $resource) {
            $privilege = $this->privilegesRepository->findOneBy([
                'name' => $attribute,
                'resource' => $resource
            ]);

            if ($privilege && $userPrivileges->contains($privilege)) {
                return true;
            }
        }

        return false;
    }
}
