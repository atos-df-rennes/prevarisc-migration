<?php

namespace App\Validator;

use App\Entity\Utilisateur;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class PasswordValidator extends ConstraintValidator
{
    /** @var bool */
    private $enforceSecurity;

    public function __construct(bool $enforceSecurity)
    {
        $this->enforceSecurity = $enforceSecurity;
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

        $password = $value->getPlainPassword();

        if (null === $password || '' === $password) {
            $this->context
                ->buildViolation($constraint->vide)
                ->atPath('utilisateur.plainPassword')
                ->addViolation()
            ;
        }

        if (
            $this->enforceSecurity
            && null !== $password
            && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W)[a-zA-Z\d\W]{8,}$/', $password)
        ) {
            $this->context
                ->buildViolation($constraint->securite)
                ->atPath('utilisateur.plainPassword')
                ->addViolation()
            ;
        }
    }
}
