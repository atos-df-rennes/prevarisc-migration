<?php

namespace App\Security\Voter;

use App\Entity\Etablissement;
use App\Entity\EtablissementAdresse;
use App\Entity\Groupement;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
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

        /** @var Etablissement $subject */
        /** @var \App\Entity\EtablissementInformations $latestInformations */
        $latestInformations = $subject->getLatestInformations();
        $genre = $latestInformations->getGenre();

        if (null === $genre) {
            return false;
        }

        return $genre->getId() === self::ID_BUP;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
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
        $userPrivileges = $userGroup->getPrivileges()->toArray();

        // Indexer les ressources par paramètre
        $resourcesByParam = [];
        foreach ($resources as $resource) {
            $name = $resource->getName();
            if (!$name) {
                continue;
            }
            // Nom attendu : etablissement_bup_{param1}_{param2}
            $parts = explode('_', $name);
            if (count($parts) !== 4) {
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
            if ($privilege && in_array($privilege, $userPrivileges, true)) {
                return true;
            }
        }

        // Règles spécifiques
        foreach ($resourcesByParam as $useUserGroupements => $subArray) {
            foreach ($subArray as $useUserCommune => $resource) {
                if ($useUserGroupements === 0 && $useUserCommune === 0) {
                    continue; // déjà traité
                }
                $privilege = $this->privilegesRepository->findOneBy([
                    'name' => $attribute,
                    'resource' => $resource
                ]);
                if ($privilege && in_array($privilege, $userPrivileges, true)) {
                    $checkGroupements = true;
                    $checkCommune = true;

                    if ($useUserGroupements === 1) {
                        $checkGroupements = false;

                        $userGroupements = $user->getGroupements();
                        $bupAdresses = $subject->getAdresses();

                        foreach ($userGroupements as $userGroupement) {
                            foreach ($bupAdresses as $bupAdresse) {
                                $commune = $bupAdresse->getCommune();

                                if ($userGroupement->getCommunes()->contains($commune)) {
                                    $checkGroupements = true;
                                    break 2;
                                }
                            }
                        }
                    }
                    if ($useUserCommune === 1) {
                        $checkCommune = false;

                        $userCommune = $user->getCommune();
                        $bupAdresses = $subject->getAdresses();

                        foreach ($bupAdresses as $bupAdresse) {
                            $commune = $bupAdresse->getCommune();

                            if ($commune === $userCommune) {
                                $checkCommune = true;
                                break;
                            }
                        }
                    }

                    return $checkGroupements && $checkCommune;
                }
            }
        }

        return false;
    }
}
