<?php

namespace App\Security\Voter\Etablissement;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Voter concret gérant uniquement la création d'un établissement (attribut CREATE = 'create_ets').
 *
 * La logique de vote est héritée de EtablissementVoter::voteOnAttribute().
 * Un utilisateur authentifié (disposant d'un groupe) peut créer un établissement.
 */
class EtablissementCreationVoter extends EtablissementVoter
{
    /**
     * Ce voter prend en charge uniquement l'attribut CREATE sans sujet.
     *
     * @param mixed $attribute
     * @param mixed $subject
     */
    protected function supports($attribute, $subject): bool
    {
        return self::CREATE === $attribute && null === $subject;
    }

    /**
     * Délègue à la logique parente (CREATE → utilisateur authentifié avec groupe).
     *
     * @param string $attribute
     * @param mixed  $subject
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        return parent::voteOnAttribute($attribute, $subject, $token);
    }
}
