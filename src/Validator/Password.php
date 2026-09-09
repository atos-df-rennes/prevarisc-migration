<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class Password extends Constraint
{
    /** @var string */
    public $vide = 'Le mot de passe ne doit pas être vide.';

    /** @var string */
    public $securite = 'Votre mot de passe doit contenir au moins 8 caractères dont 1 minuscule, 1 majuscule, 1 chiffre et 1 caractère spécial.';

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
