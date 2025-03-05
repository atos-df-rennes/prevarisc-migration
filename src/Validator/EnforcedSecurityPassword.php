<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

final class EnforcedSecurityPassword extends Constraint
{
    /** @var string */
    public $message = 'Votre mot de passe doit contenir au moins 8 caractères dont 1 minuscule, 1 majuscule, 1 chiffre et 1 caractère spécial.';
}
