<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Contrainte de validation d'unicité conditionnelle pour Utilisateur.
 *
 * Règles métier :
 * - Si login (username) renseigné → vérifier unicité du login
 * - Si login vide → vérifier unicité du mail (dans UtilisateurInformations)
 */
#[\Attribute]
final class UniqueUtilisateur extends Constraint
{
    /** @var string Message d'erreur pour login déjà utilisé */
    public $usernameMessage = "Ce nom d'utilisateur est déjà utilisé. Veuillez en choisir un autre.";

    /** @var string Message d'erreur pour mail déjà utilisé */
    public $mailMessage = 'Cette adresse email est déjà utilisée. Veuillez en choisir une autre.';

    /**
     * La contrainte s'applique à la classe entière (pas à une propriété).
     */
    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
