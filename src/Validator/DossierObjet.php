<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint pour valider que le champ OBJET est rempli
 * uniquement si la nature du dossier le requiert.
 *
 * @Annotation
 */
final class DossierObjet extends Constraint
{
    /** @var string */
    public $message = 'Le champ "Objet" est obligatoire pour cette nature de dossier.';

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    #[\Override]
    public function validatedBy(): string
    {
        return DossierObjetValidator::class;
    }
}
