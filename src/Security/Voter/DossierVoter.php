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

        // Vérification du privilège "toutes natures" (id=0)
        foreach ($resources as $resource) {
            [, $natureIdStr] = explode('_', $resource->getName());
            $natureId = (int) $natureIdStr;

            if ($natureId === 0) {
                $privilegeAll = $this->privilegesRepository->findOneBy(['name' => $attribute, 'resource' => $resource]);
                if ($privilegeAll && in_array($privilegeAll, $userPrivileges, true)) {
                    return true;
                }
                break;
            }
        }

        // Vérification du privilège spécifique à la nature du dossier
        foreach ($resources as $resource) {
            [, $natureIdStr] = explode('_', $resource->getName());
            $natureId = (int) $natureIdStr;

            if ($natureDossierId === $natureId) {
                $privilegeSpecific = $this->privilegesRepository->findOneBy(['name' => $attribute, 'resource' => $resource]);
                if ($privilegeSpecific && in_array($privilegeSpecific, $userPrivileges, true)) {
                    return true;
                }
                break;
            }
        }

        return false;
    }
}
