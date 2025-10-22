<?php

namespace App\Security\Voter;

use App\Entity\Dossier;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DossierVoter extends Voter
{
    public const LECTURE = 'view_doss';

    public const MODIFICATION = 'edit_doss';

    public const VERROUILLAGE = 'verrouillage_dossier';

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
        if (false === in_array($attribute, [self::LECTURE, self::MODIFICATION, self::VERROUILLAGE], true)) {
            return false;
        }

        $resources = $this->resourcesRepository->findRessourcesSpecialiseesDossier();

        // S'il n'y a pas de ressources spécialisées, tout le monde a accès à tout.
        if ([] === $resources) {
            return false;
        }

        return $subject instanceof Dossier;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof Utilisateur) {
            return false;
        }

        if (!$subject instanceof Dossier) {
            return false;
        }

        $natureDossier = $subject->getNature();

        if (null === $natureDossier) {
            return false;
        }

        $natureDossierId = $natureDossier->getId();

        if (null === $natureDossierId) {
            return false;
        }

        $resources = $this->resourcesRepository->findRessourcesSpecialiseesDossier();
        $userGroup = $user->getGroupe();

        if (null === $userGroup) {
            return false;
        }

        $userPrivileges = $userGroup->getPrivileges()->toArray();

        // Indexer les ressources par identifiant
        /** @var array<int, Resources> $resourcesById */
        $resourcesById = [];
        foreach ($resources as $resource) {
            $name = $resource->getName();

            if (null === $name) {
                continue;
            }

            $parts = explode('_', $name);
            if (count($parts) < 2) {
                continue;
            }

            $natureIdStr = $parts[1];
            $natureId = (int) $natureIdStr;
            $resourcesById[$natureId] = $resource;
        }

        // Vérification du privilège "toutes natures" (id=0)
        if (array_key_exists(0, $resourcesById)) {
            $privilegeAll = $this->privilegesRepository->findOneBy(['name' => $attribute, 'resource' => $resourcesById[0]]);
            if ($privilegeAll && in_array($privilegeAll, $userPrivileges, true)) {
                return true;
            }
        }

        // Vérification du privilège spécifique à la nature du dossier
        if (array_key_exists($natureDossierId, $resourcesById)) {
            $privilegeSpecific = $this->privilegesRepository->findOneBy(['name' => $attribute, 'resource' => $resourcesById[$natureDossierId]]);
            if ($privilegeSpecific && in_array($privilegeSpecific, $userPrivileges, true)) {
                return true;
            }
        }

        return false;
    }
}
