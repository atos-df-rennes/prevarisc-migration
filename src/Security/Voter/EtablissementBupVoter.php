<?php

namespace App\Security\Voter;

use App\Entity\Etablissement;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Service\Voter\VoterUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EtablissementBupVoter extends EtablissementVoter
{
    private const ID_BUP = 6;

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
        if (!parent::supports($attribute, $subject)) {
            return false;
        }

        return parent::isSubjectGenreAllowed($subject, self::ID_BUP);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        // @todo: optimiser en factorisant ces vérifications.
        $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissement(self::ID_BUP);
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

            // Nom attendu : etablissement_bup_{param1}_{param2}
            $parts = explode('_', $name);
            if (4 !== count($parts)) {
                continue;
            }

            $useUserGroupements = (int) $parts[2];
            $useUserCommune = (int) $parts[3];
            $resourcesByParam[$useUserGroupements][$useUserCommune] = $resource;
        }

        // Ignorer les groupements et les communes, donne accès à tout.
        if (isset($resourcesByParam[0][0])) {
            $privilege = $this->privilegesRepository->findOneBy([
                'name' => $attribute,
                'resource' => $resourcesByParam[0][0]
            ]);
            if ($privilege && $userPrivileges->contains($privilege)) {
                return true;
            }
        }

        // Règles spécifiques
        foreach ($resourcesByParam as $useUserGroupements => $subArray) {
            foreach ($subArray as $useUserCommune => $resource) {
                if (0 === $useUserGroupements && 0 === $useUserCommune) {
                    continue; // déjà traité
                }

                $privilege = $this->privilegesRepository->findOneBy([
                    'name' => $attribute,
                    'resource' => $resource
                ]);
                if ($privilege && $userPrivileges->contains($privilege)) {
                    return VoterUtils::isUserGroupementsAndCommuneAllowed($user, $subject, $useUserGroupements, $useUserCommune);
                }
            }
        }

        return false;
    }
}
