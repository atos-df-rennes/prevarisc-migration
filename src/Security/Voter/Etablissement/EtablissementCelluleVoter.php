<?php

namespace App\Security\Voter\Etablissement;

use App\Constant\GenreEtablissement;
use App\Entity\Etablissement;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EtablissementCelluleVoter extends EtablissementVoter
{
    public function __construct(private readonly ResourcesRepository $resourcesRepository, private readonly PrivilegesRepository $privilegesRepository)
    {
    }

    protected function supports($attribute, $subject): bool
    {
        return parent::supports($attribute, $subject)
            && parent::isSubjectGenreAllowed($subject, GenreEtablissement::CELLULE);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!parent::voteOnAttribute($attribute, $subject, $token)) {
            return false;
        }

        /** @var Etablissement $subject */
        $resources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissementCellule($subject->getLatestInformations()->getTypeActivite());
        if ([] === $resources) {
            return false;
        }

        /** @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Privileges> $userPrivileges */
        /** @phpstan-ignore-next-line */
        $userPrivileges = $token->getUser()->getGroupe()->getPrivileges();

        foreach ($resources as $resource) {
            $privilege = $this->privilegesRepository->findOneBy(['name' => $attribute, 'resource' => $resource]);

            if ($privilege && $userPrivileges->contains($privilege)) {
                return true;
            }
        }

        return false;
    }
}
