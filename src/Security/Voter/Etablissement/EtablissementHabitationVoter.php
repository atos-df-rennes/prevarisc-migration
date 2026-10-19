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

final class EtablissementHabitationVoter extends EtablissementVoter
{
    public function __construct(private readonly ResourcesRepository $resourcesRepository, private readonly PrivilegesRepository $privilegesRepository, private readonly VoterUtils $voterUtils)
    {
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!parent::supports($attribute, $subject)) {
            return false;
        }

        return parent::isSubjectGenreAllowed($subject, GenreEtablissement::HABITATION);
    }

    #[\Override]
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (!parent::voteOnAttribute($attribute, $subject, $token)) {
            return false;
        }

        /** @var Etablissement $subject */
        $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissementHabitation($subject->getLatestInformations()->getFamille());
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
            if (5 !== count($parts)) {
                continue;
            }

            $useUserGroupements = (int) $parts[3];
            $useUserCommune = (int) $parts[4];

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
