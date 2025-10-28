<?php

namespace App\Security\Voter;

use App\Entity\Etablissement;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EtablissementSiteVoter extends EtablissementVoter
{
    public const ID_SITE = 1;

    /** @var \App\Security\Voter\EtablissementErpVoter */
    private $erpVoter;

    public function __construct(EtablissementErpVoter $erpVoter)
    {
        $this->erpVoter = $erpVoter;
    }

    protected function supports($attribute, $subject): bool
    {
        return parent::supports($attribute, $subject)
            && parent::isSubjectGenreAllowed($subject, self::ID_SITE);
    }

    /**
     * L'accès à un site est accordé si l'accès à un de ses fils est accordé.
     * Un site n'a que des fils de genre ERP.
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!parent::voteOnAttribute($attribute, $subject, $token)) {
            return false;
        }

        /** @var Etablissement $subject */
        foreach ($subject->getFils() as $etablissementFils) {
            if (VoterInterface::ACCESS_GRANTED === $this->erpVoter->vote($token, $etablissementFils, [$attribute])) {
                return true;
            }
        }

        return false;
    }
}
