<?php

namespace App\Security\Voter;

use App\Entity\Privileges;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Security\Voter\Etablissement\EtablissementVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PrivilegeVoter implements VoterInterface
{
    /** @var PrivilegesRepository */
    private $privilegesRepository;

    /**
     * Index en mémoire des privileges (name → entité), chargé en une seule requête.
     * Ce voter est stateful par conception : acceptable dans un contexte web classique
     * (requête HTTP courte). Si l'infrastructure évolue vers un environnement long-running
     * (Messenger workers, RoadRunner, Swoole…), implémenter ResetInterface ou utiliser
     * un cache applicatif avec TTL/invalidation.
     *
     * @var array<string, Privileges>|null
     */
    private $privilegesIndex;

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

        if (!is_string($attribute) || in_array($attribute, self::EXCLUDED_ATTRIBUTES)) {
            return self::ACCESS_ABSTAIN;
        }

        $privilege = $this->findPrivilegeByName($attribute);

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

    /**
     * Charge tous les privileges en une seule requête et retourne celui correspondant au nom demandé.
     * Évite un SELECT par appel à isGranted() (typiquement ~18 sur la page d'accueil).
     */
    private function findPrivilegeByName(string $name): ?Privileges
    {
        if (null === $this->privilegesIndex) {
            $this->privilegesIndex = [];
            foreach ($this->privilegesRepository->findAll() as $privilege) {
                $privilegeName = $privilege->getName();

                if (null === $privilegeName) {
                    continue;
                }

                $this->privilegesIndex[$privilegeName] = $privilege;
            }
        }

        return $this->privilegesIndex[$name] ?? null;
    }
}
