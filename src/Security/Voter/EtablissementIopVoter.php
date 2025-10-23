<?php

namespace App\Security\Voter;

use App\Entity\Etablissement;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EtablissementIopVoter extends EtablissementVoter
{
    private const ID_IOP = 9;

    /**
     * @var ResourcesRepository
     */
    private $resourcesRepository;

    /**
     * @var PrivilegesRepository
     */
    private $privilegesRepository;

    /**
     * @var \Psr\Log\LoggerInterface
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

        /** @var Etablissement $subject */
        /** @var \App\Entity\EtablissementInformations $latestInformations */
        $latestInformations = $subject->getLatestInformations();
        $genre = $latestInformations->getGenre();

        if (null === $genre) {
            return false;
        }

        return $genre->getId() === self::ID_IOP;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissement(self::ID_IOP);

        if ([] === $resources) {
            return false;
        }

        if (count($resources) > 1) {
            $this->logger->warning("Plusieurs règles trouvées pour les IOP. Il est recommandé de supprimer les doublons.");

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

        $privilege = $this->privilegesRepository->findOneBy(['name' => $attribute, 'resource' => $resources[0]]);
        if ($privilege && in_array($privilege, $userPrivileges, true)) {
            return true;
        }

        return false;
    }
}
