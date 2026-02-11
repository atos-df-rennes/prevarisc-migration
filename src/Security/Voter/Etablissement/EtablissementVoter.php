<?php

namespace App\Security\Voter\Etablissement;

use App\Entity\Etablissement;
use App\Entity\EtablissementInformations;
use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

abstract class EtablissementVoter extends Voter
{
    public const LECTURE = 'view_ets';

    public const MODIFICATION = 'edit_ets';

    protected function supports($attribute, $subject): bool
    {
        if (false === in_array($attribute, [self::LECTURE, self::MODIFICATION], true)) {
            return false;
        }

        return $subject instanceof Etablissement;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof Utilisateur) {
            return false;
        }

        $userGroupe = $user->getGroupe();

        return null !== $userGroupe;
    }

    /**
     * @phpstan-ignore missingType.parameter
     */
    protected function isSubjectGenreAllowed($subject, int $genreId): bool
    {
        /** @var Etablissement $subject */
        /** @var EtablissementInformations $latestInformations */
        $latestInformations = $subject->getLatestInformations();
        $genre = $latestInformations->getGenre();

        if (null === $genre) {
            return false;
        }

        return $genreId === $genre->getId();
    }
}
