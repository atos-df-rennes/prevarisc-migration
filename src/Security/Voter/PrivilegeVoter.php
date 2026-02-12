<?php

namespace App\Security\Voter;

use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Security\Voter\Etablissement\EtablissementVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PrivilegeVoter implements VoterInterface
{
    /** @var PrivilegesRepository */
    private $privilegesRepository;

    private const EXCLUDED_ATTRIBUTES = [
        DossierVoter::LECTURE,
        DossierVoter::MODIFICATION,
        DossierVoter::VERROUILLAGE,
        EtablissementVoter::LECTURE,
        EtablissementVoter::MODIFICATION,
    ];

    public function __construct(PrivilegesRepository $privilegesRepository)
    {
        $this->privilegesRepository = $privilegesRepository;
    }

    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        $attribute = $attributes[0];

        if (in_array($attribute, self::EXCLUDED_ATTRIBUTES)) {
            return self::ACCESS_ABSTAIN;
        }

        $privilege = $this->privilegesRepository->findOneBy(['name' => $attribute]);

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
