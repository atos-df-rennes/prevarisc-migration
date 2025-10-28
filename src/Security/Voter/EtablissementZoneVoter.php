<?php

namespace App\Security\Voter;

use App\Entity\Etablissement;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EtablissementZoneVoter extends EtablissementVoter
{
    public const ID_ZONE = 10;

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
            && parent::isSubjectGenreAllowed($subject, self::ID_ZONE);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissementZone($subject->getLatestInformations()->getClassement());
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

        foreach ($resources as $resource) {
            $privilege = $this->privilegesRepository->findOneBy(['name' => $attribute, 'resource' => $resource]);

            if ($privilege && $userPrivileges->contains($privilege)) {
                return true;
            }
        }

        return false;
    }
}
