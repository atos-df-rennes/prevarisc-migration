<?php

namespace App\Security\Voter\Etablissement;

use App\Constant\GenreEtablissement;
use App\Entity\Etablissement;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Service\Voter\VoterUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EtablissementErpVoter extends EtablissementVoter
{
    public function __construct(private readonly ResourcesRepository $resourcesRepository, private readonly PrivilegesRepository $privilegesRepository, private readonly VoterUtils $voterUtils)
    {
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return parent::supports($attribute, $subject) && parent::isSubjectGenreAllowed($subject, GenreEtablissement::ERP);
    }

    #[\Override]
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!parent::voteOnAttribute($attribute, $subject, $token)) {
            return false;
        }

        /** @var Etablissement $subject */
        $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissementErp(
            $subject->getLatestInformations()->getTypeActivite(),
            $subject->getLatestInformations()->getCategorie()
        );
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
            if (7 !== count($parts)) {
                continue;
            }

            $typesActivites = array_map(intval(...), explode('-', $parts[2]));
            $categories = array_map(intval(...), explode('-', $parts[3]));
            $useUserCommissions = (int) $parts[4];
            $useUserGroupements = (int) $parts[5];
            $useUserCommune = (int) $parts[6];

            if (
                $privilege
                && $userPrivileges->contains($privilege)
                && $this->voterUtils->isTypeActiviteAndCategorieAllowed($subject, $typesActivites, $categories)
                && $this->voterUtils->isUserGroupementsAndCommuneAllowed($user, $subject, $useUserGroupements, $useUserCommune)
                && $this->voterUtils->isUserCommissionsAllowed($user, $subject, $useUserCommissions)
            ) {
                return true;
            }
        }

        return false;
    }
}
