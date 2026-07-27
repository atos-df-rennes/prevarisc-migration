<?php

namespace App\Validator;

use App\Entity\Utilisateur;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class PasswordValidator extends ConstraintValidator
{
    public function __construct(private bool $enforceSecurity)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Password) {
            throw new UnexpectedTypeException($constraint, Password::class);
        }

        if (!$value instanceof Utilisateur) {
            throw new UnexpectedValueException($value, Utilisateur::class);
        }

        // Pas de validation sur le mot de passe si on a indiqué une connexion SSO
        if ($value->getSso()) {
            return;
        }

        // Pas de validation s'il n'y a pas de login, ce n'est pas réellement un utilisateur
        if ('' === $value->getUsername()) {
            return;
        }

        $plainPassword = $value->getPlainPassword();
        $password = $value->getPassword();

        /*
         * S'il y a actuellement un mot de passe et que le nouveau mot de passe est null,
         * c'est qu'il n'a pas été modifié, on n'émet pas d'erreur dans ce cas.
         */
        if (
            '' === $plainPassword
            || (null === $plainPassword && null === $password)
        ) {
            $this->context
                ->buildViolation($constraint->vide)
                ->atPath('utilisateur.plainPassword')
                ->addViolation()
            ;

            return;
        }

        if (
            $this->enforceSecurity
            && null !== $plainPassword
            && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W)[a-zA-Z\d\W]{8,}$/', $plainPassword)
        ) {
            $this->context
                ->buildViolation($constraint->securite)
                ->atPath('utilisateur.plainPassword')
                ->addViolation()
            ;
        }
    }
}
