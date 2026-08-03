<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Vérifie que le dossier incomplet possède au moins un document manquant
 * avec les champs obligatoires (date et documentManquant) renseignés.
 *
 * @Annotation
 */
final class DossierDocumentsManquants extends Constraint
{
    /** @var string */
    public $messageCollectionVide = 'Le dossier est marqué incomplet mais aucun document manquant n\'a été renseigné.';

    /** @var string */
    public $messageChampsManquants = 'Chaque document manquant doit avoir une date d\'incomplétude et un contenu renseignés.';

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    #[\Override]
    public function validatedBy(): string
    {
        return DossierDocumentsManquantsValidator::class;
    }
}
