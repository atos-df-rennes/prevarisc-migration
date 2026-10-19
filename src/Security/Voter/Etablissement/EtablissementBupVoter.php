<?php

namespace App\Security\Voter\Etablissement;

use App\Constant\GenreEtablissement;
use App\Entity\Etablissement;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Service\Voter\VoterUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

final class EtablissementBupVoter extends EtablissementVoter
{
    public function __construct(private readonly ResourcesRepository $resourcesRepository, private readonly PrivilegesRepository $privilegesRepository, private readonly VoterUtils $voterUtils)
    {
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return parent::supports($attribute, $subject) && parent::isSubjectGenreAllowed($subject, GenreEtablissement::BUP);
    }

    #[\Override]
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (!parent::voteOnAttribute($attribute, $subject, $token)) {
            return false;
        }

        $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissementBup();
        if ([] === $resources) {
            return false;
        }

        /** @var Utilisateur $user */
        $user = $token->getUser();
        /** @phpstan-ignore-next-line */
        $userPrivileges = $user->getGroupe()->getPrivileges();

        foreach ($resources as $resource) {
            $privilege = $this->privilegesRepository->findOneBy(['name' => $attribute, 'resource' => $resource]);

            $resourceName = $resource->getName();
            if (!$resourceName) {
                continue;
            }

            $parts = explode('_', $resourceName);
            if (4 !== count($parts)) {
                continue;
            }

            $useUserGroupements = (int) $parts[2];
            $useUserCommune = (int) $parts[3];

            /** @var Etablissement $subject */
            if (
                $privilege
                && $userPrivileges->contains($privilege)
                && $this->voterUtils->isUserGroupementsAndCommuneAllowed($user, $subject, $useUserGroupements, $useUserCommune)
            ) {
                return true;
            }
        }

        return false;
    }
}
