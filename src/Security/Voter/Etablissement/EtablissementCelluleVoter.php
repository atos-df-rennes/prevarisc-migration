<?php

namespace App\Security\Voter\Etablissement;

use App\Constant\GenreEtablissement;
use App\Entity\Etablissement;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Service\Voter\VoterUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EtablissementCelluleVoter extends EtablissementVoter
{
    /**
     * @var ResourcesRepository
     */
    private $resourcesRepository;

    /**
     * @var PrivilegesRepository
     */
    private $privilegesRepository;

    /** @var VoterUtils */
    private $voterUtils;

    public function __construct(ResourcesRepository $resourcesRepository, PrivilegesRepository $privilegesRepository, VoterUtils $voterUtils)
    {
        $this->resourcesRepository = $resourcesRepository;
        $this->privilegesRepository = $privilegesRepository;
        $this->voterUtils = $voterUtils;
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

            $typesActivites = array_map('intval', explode('-', $parts[2]));
            $categories = array_map('intval', explode('-', $parts[3]));

            if ($privilege && $userPrivileges->contains($privilege)
                && $this->voterUtils->isTypeActiviteAndCategorieAllowed($subject, $typesActivites, $categories)
            ) {
                return true;
            }
        }

        return false;
    }
}
