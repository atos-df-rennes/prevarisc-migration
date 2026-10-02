<?php

namespace App\Security\Voter\Etablissement;

use App\Constant\GenreEtablissement;
use App\Entity\Etablissement;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Service\Voter\VoterUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class EtablissementCelluleVoter extends EtablissementVoter
{
    public function __construct(
        private readonly ResourcesRepository $resourcesRepository,
        private readonly PrivilegesRepository $privilegesRepository,
        private readonly VoterUtils $voterUtils,
    ) {
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return parent::supports($attribute, $subject)
            && parent::isSubjectGenreAllowed($subject, GenreEtablissement::CELLULE);
    }

    #[\Override]
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (!parent::voteOnAttribute($attribute, $subject, $token)) {
            return false;
        }

        /** @var Etablissement $subject */
        $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissementCellule(
            $subject->getLatestInformations()->getTypeActivite(),
            $subject->getCategorieEffective()
        );
        if ([] === $resources) {
            return false;
        }

        /** @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Privileges> $userPrivileges */
        /** @phpstan-ignore-next-line */
        $userPrivileges = $token->getUser()->getGroupe()->getPrivileges();

        foreach ($resources as $resource) {
            $privilege = $this->privilegesRepository->findOneBy(['name' => $attribute, 'resource' => $resource]);
            $parts = explode('_', $resource->getName() ?? '');
            if (4 !== count($parts)) {
                continue;
            }

            $typesActivites = array_map(intval(...), explode('-', $parts[2]));
            $categories = array_map(intval(...), explode('-', $parts[3]));

            if ($privilege && $userPrivileges->contains($privilege)
                && $this->voterUtils->isTypeActiviteAndCategorieAllowed($subject, $typesActivites, $categories)
            ) {
                return true;
            }
        }

        return false;
    }
}
