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

    /**
     * Attribut permettant de vérifier si l'utilisateur peut créer un établissement.
     * Un utilisateur authentifié (ayant un groupe) peut créer un établissement.
     */
    public const CREATE = 'create_ets';

    protected function supports($attribute, $subject): bool
    {
        // L'attribut CREATE ne nécessite pas de sujet (établissement inexistant à ce stade)
        if (self::CREATE === $attribute) {
            return null === $subject;
        }

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

        // Pour la création : tout utilisateur authentifié avec un groupe peut créer
        if (self::CREATE === $attribute) {
            return null !== $user->getGroupe();
        }

        $userGroupe = $user->getGroupe();

        return null !== $userGroupe;
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

        /** @var EtablissementInformations $latestInformations */
        $latestInformations = $subject->getLatestInformations();
        $genre = $latestInformations->getGenre();

        if (null === $genre) {
            return false;
        }

        return $genreId === $genre->getId();
    }
}
