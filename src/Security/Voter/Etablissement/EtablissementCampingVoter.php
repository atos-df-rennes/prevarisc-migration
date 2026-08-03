<?php

namespace App\Security\Voter\Etablissement;

use App\Constant\GenreEtablissement;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EtablissementCampingVoter extends EtablissementVoter
{
    public function __construct(private readonly ResourcesRepository $resourcesRepository, private readonly PrivilegesRepository $privilegesRepository, private readonly LoggerInterface $logger)
    {
    }

    #[\Override]
    protected function supports($attribute, $subject): bool
    {
        if (!parent::supports($attribute, $subject)) {
            return false;
        }

        return parent::isSubjectGenreAllowed($subject, GenreEtablissement::CAMPING);
    }

    #[\Override]
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!parent::voteOnAttribute($attribute, $subject, $token)) {
            return false;
        }

        try {
            $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissementCamping();
        } catch (NoResultException) {
            $this->logger->warning("Aucune règle trouvée pour les campings. Il est recommandé d'en ajouter une.");

            return false;
        } catch (NonUniqueResultException) {
            $this->logger->warning('Plusieurs règles trouvées pour les campings. Il est recommandé de supprimer les doublons.');

            return false;
        }

        /** @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Privileges> $userPrivileges */
        /** @phpstan-ignore-next-line */
        $userPrivileges = $token->getUser()->getGroupe()->getPrivileges();

        $privilege = $this->privilegesRepository->findOneBy(['name' => $attribute, 'resource' => $resources]);

        return $privilege && $userPrivileges->contains($privilege);
    }
}
