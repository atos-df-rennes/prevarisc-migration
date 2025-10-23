<?php

namespace App\Security\Voter;

use App\Entity\Etablissement;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EtablissementCampingVoter extends EtablissementVoter
{
    private const ID_CAMPING = 7;

    /**
     * @var ResourcesRepository
     */
    private $resourcesRepository;

    /**
     * @var PrivilegesRepository
     */
    private $privilegesRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ResourcesRepository $resourcesRepository, PrivilegesRepository $privilegesRepository, LoggerInterface $logger)
    {
        $this->resourcesRepository = $resourcesRepository;
        $this->privilegesRepository = $privilegesRepository;
        $this->logger = $logger;
    }

    protected function supports($attribute, $subject): bool
    {
        if (!parent::supports($attribute, $subject)) {
            return false;
        }

        return parent::isSubjectGenreAllowed($subject, self::ID_CAMPING);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissement(self::ID_CAMPING);

        if ([] === $resources) {
            return false;
        }

        if (count($resources) > 1) {
            $this->logger->warning('Plusieurs règles trouvées pour les campings. Il est recommandé de supprimer les doublons.');

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

        $privilege = $this->privilegesRepository->findOneBy(['name' => $attribute, 'resource' => $resources[0]]);

        return $privilege && $userPrivileges->contains($privilege);
    }
}
