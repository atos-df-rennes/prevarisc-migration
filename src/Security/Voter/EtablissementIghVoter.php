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
    public const ID_IGH = 5;

    /** @var ResourcesRepository */
    private $resourcesRepository;

    /** @var PrivilegesRepository */
    private $privilegesRepository;

    /** @var VoterUtils */
    private $voterUtils;

    public function __construct(ResourcesRepository $resourcesRepository, PrivilegesRepository $privilegesRepository, VoterUtils $voterUtils)
    {
        $this->resourcesRepository = $resourcesRepository;
        $this->privilegesRepository = $privilegesRepository;
        $this->voterUtils = $voterUtils;
    }

    protected function supports($attribute, $subject): bool
    {
        return parent::supports($attribute, $subject) && parent::isSubjectGenreAllowed($subject, self::ID_IGH);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!parent::voteOnAttribute($attribute, $subject, $token)) {
            return false;
        }

        /** @var Etablissement $subject */
        $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissementIgh($subject->getLatestInformations()->getClasse());
        if ([] === $resources) {
            return false;
        }

        /** @var Utilisateur $user */
        $user = $token->getUser();
        /** @phpstan-ignore-next-line */
        $userPrivileges = $user->getGroupe()->getPrivileges();

        foreach ($resources as $resource) {
            $privilege = $this->privilegesRepository->findOneBy(['name' => $attribute, 'resource' => $resource]);

            $resourceName = $resource->getName();
            if (!$resourceName) {
                continue;
            }

            $parts = explode('_', $resourceName);
            if (6 !== count($parts)) {
                continue;
            }

            $useUserCommissions = (int) $parts[3];
            $useUserGroupements = (int) $parts[4];
            $useUserCommune = (int) $parts[5];

            if (
                $privilege
                && $userPrivileges->contains($privilege)
                && $this->voterUtils->isUserGroupementsAndCommuneAllowed($user, $subject, $useUserGroupements, $useUserCommune)
                && $this->voterUtils->isUserCommissionsAllowed($user, $subject, $useUserCommissions)
            ) {
                return true;
            }
        }

        return false;
    }
}
