<?php

namespace App\Security\Voter;

use App\Entity\Dossier;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class DossierVoter extends Voter
{
    public const LECTURE = 'view_doss';
    public const MODIFICATION = 'edit_doss';
    public const VERROUILLAGE = 'verrouillage_dossier';

    /** @var ResourcesRepository */
    private $resourcesRepository;

    /** @var PrivilegesRepository */
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

        $natureDossier = $subject->getNature()->getId();

        if (null === $natureDossier) {
            return false;
        }

        $resources = $this->resourcesRepository->findRessourcesSpecialiseesDossier();
        $resourceConcernee = null;

        foreach ($resources as $resource) {
            [, $naturesResources] = explode('_', $resource->getName());
            $natureResource = explode('-', $naturesResources);

            if (count($natureResource) > 1) {
                continue;
            }

            if ($natureDossier !== (int) $natureResource[0]) {
                continue;
            }

            $resourceConcernee = $resource;
        }

        if (null === $resourceConcernee) {
            return false;
        }

        $privilege = $this->privilegesRepository->findOneBy(['name' => $attribute, 'resource' => $resourceConcernee]);
        $userGroup = $user->getGroupe();

        if (null === $userGroup) {
            return false;
        }

        $userPrivileges = $userGroup->getPrivileges()->toArray();

        return in_array($privilege, $userPrivileges, true);
    }
}
