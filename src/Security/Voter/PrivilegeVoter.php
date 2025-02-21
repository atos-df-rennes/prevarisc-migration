<?php

namespace App\Security\Voter;

use App\Entity\Privileges;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PrivilegeVoter implements VoterInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        $attribute = $attributes[0];

        /** @var PrivilegesRepository */
        $privilegesRepository = $this->entityManager->getRepository(Privileges::class);
        $privilege = $privilegesRepository->findOneBy(['name' => $attribute]);

        if (null === $privilege) {
            return self::ACCESS_ABSTAIN;
        }

        $user = $token->getUser();

        if (!$user instanceof Utilisateur) {
            return self::ACCESS_ABSTAIN;
        }

        $userGroup = $user->getGroupe();

        if (null === $userGroup) {
            return self::ACCESS_ABSTAIN;
        }

        $userPrivileges = $userGroup->getPrivileges()->toArray();

        if (in_array($privilege, $userPrivileges)) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_DENIED;
    }
}
