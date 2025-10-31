<?php

namespace App\Security\Voter;

use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EtablissementManifestationTemporaireVoter extends EtablissementVoter
{
    public const ID_MANIFESTATION_TEMPORAIRE = 8;

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

        return parent::isSubjectGenreAllowed($subject, self::ID_MANIFESTATION_TEMPORAIRE);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!parent::voteOnAttribute($attribute, $subject, $token)) {
            return false;
        }

        try {
            $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissementManifestationTemporaire();
        } catch (NoResultException $noResultException) {
            $this->logger->warning("Aucune règle trouvée pour les manifestations temporaires. Il est recommandé d'en ajouter une.");

            return false;
        } catch (NonUniqueResultException $nonUniqueResultException) {
            $this->logger->warning('Plusieurs règles trouvées pour les manifestations temporaires. Il est recommandé de supprimer les doublons.');

            return false;
        }

        /** @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Privileges> $userPrivileges */
        /** @phpstan-ignore-next-line */
        $userPrivileges = $token->getUser()->getGroupe()->getPrivileges();

        $privilege = $this->privilegesRepository->findOneBy(['name' => $attribute, 'resource' => $resources]);

        return $privilege && $userPrivileges->contains($privilege);
    }
}
