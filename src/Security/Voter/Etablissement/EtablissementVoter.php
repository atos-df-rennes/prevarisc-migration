<?php

namespace App\Security\Voter\Etablissement;

use App\Entity\Etablissement;
use App\Entity\Genre;
use App\Entity\Groupe;
use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

abstract class EtablissementVoter extends Voter
{
    public const LECTURE = 'view_ets';

    public const MODIFICATION = 'edit_ets';

    /**
     * Attribut permettant de vérifier si l'utilisateur peut créer un établissement.
     * Le contrôle est délégué au PrivilegeVoter qui vérifie le privilège 'add_etablissement' en BDD.
     */
    public const CREATE = 'add_etablissement';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (false === in_array($attribute, [self::LECTURE, self::MODIFICATION], true)) {
            return false;
        }

        return $subject instanceof Etablissement;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof Utilisateur) {
            return false;
        }

        $userGroupe = $user->getGroupe();

        return $userGroupe instanceof Groupe;
    }

    /**
     * Vérifie si le genre de l'établissement sujet correspond au genre attendu.
     * Retourne false si le sujet n'est pas un établissement (ex: null pour la création).
     *
     * @phpstan-ignore missingType.parameter
     */
    protected function isSubjectGenreAllowed($subject, int $genreId): bool
    {
        if (!$subject instanceof Etablissement) {
            return false;
        }

        $latestInformations = $subject->getLatestInformations();
        $genre = $latestInformations->getGenre();

        if (!$genre instanceof Genre) {
            return false;
        }

        return $genreId === $genre->getId();
    }
}
