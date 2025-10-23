<?php

namespace App\Security\Voter;

use App\Entity\Etablissement;
use App\Entity\EtablissementInformations;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EtablissementZoneVoter extends EtablissementVoter
{
    private const ID_ZONE = 10;

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
        /** @var EtablissementInformations $latestInformations */
        $latestInformations = $subject->getLatestInformations();
        $genre = $latestInformations->getGenre();

        if (null === $genre) {
            return false;
        }

        return self::ID_ZONE === $genre->getId();
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissement(self::ID_ZONE);

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

        // Indexer les ressources par identifiant
        /** @var array<int, array<int, Resources>> $resourcesById */
        $resourcesById = [];
        foreach ($resources as $resource) {
            $name = $resource->getName();

            if (null === $name) {
                continue;
            }

            $parts = explode('_', $name);
            if (count($parts) < 5) {
                continue;
            }

            $classementsIdStr = explode('-', $parts[2]);

            foreach ($classementsIdStr as $classementIdStr) {
                $classementId = (int) $classementIdStr;
                $resourcesById[$classementId][] = $resource;
            }
        }

        // Vérification du privilège "toutes classes" (id=0)
        if (array_key_exists(0, $resourcesById)) {
            $privilegeAll = $this->privilegesRepository->findOneBy(['name' => $attribute, 'resource' => $resourcesById[0]]);
            if ($privilegeAll && in_array($privilegeAll, $userPrivileges, true)) {
                return true;
            }
        }

        $zoneClassement = $subject->getLatestInformations()->getClassement();

        if (null === $zoneClassement) {
            return false;
        }

        $zoneClassementId = $zoneClassement->getId();

        if (null === $zoneClassementId) {
            return false;
        }

        if (array_key_exists($zoneClassementId, $resourcesById)) {
            foreach ($resourcesById[$zoneClassementId] as $resource) {
                $privilegeSpecific = $this->privilegesRepository->findOneBy([
                    'name' => $attribute,
                    'resource' => $resource
                ]);
                if ($privilegeSpecific && in_array($privilegeSpecific, $userPrivileges, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
