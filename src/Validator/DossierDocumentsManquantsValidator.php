<?php

namespace App\Validator;

use App\Entity\Dossier;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validator pour la contrainte DossierDocumentsManquants.
 *
 * Si le dossier est marqué incomplet, vérifie :
 * - qu'au moins un DossierDocumentManquant est présent dans la collection
 * - que chaque entrée a les champs `date` et `documentManquant` renseignés
 * - `dateReception` reste nullable et n'est pas vérifiée
 */
final class DossierDocumentsManquantsValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof DossierDocumentsManquants) {
            throw new UnexpectedTypeException($constraint, DossierDocumentsManquants::class);
        }

        if (!$value instanceof Dossier) {
            throw new UnexpectedValueException($value, Dossier::class);
        }

        // Contrainte applicable uniquement si le dossier est marqué incomplet
        if (!$value->isIncomplet()) {
            return;
        }

        $documentsManquants = $value->getDocumentsManquants();

        // Au moins un élément doit être présent
        if ($documentsManquants->isEmpty()) {
            $this->context
                ->buildViolation($constraint->messageCollectionVide)
                ->atPath('documentsManquants')
                ->addViolation()
            ;

            return;
        }

        // Chaque élément doit avoir date et documentManquant renseignés
        foreach ($documentsManquants as $index => $doc) {
            if (null === $doc->getDate()) {
                $this->context
                    ->buildViolation($constraint->messageChampsManquants)
                    ->atPath(sprintf('documentsManquants[%d].date', $index))
                    ->addViolation()
                ;
            }

            $documentManquant = $doc->getDocumentManquant();
            if (null === $documentManquant || '' === trim($documentManquant)) {
                $this->context
                    ->buildViolation($constraint->messageChampsManquants)
                    ->atPath(sprintf('documentsManquants[%d].documentManquant', $index))
                    ->addViolation()
                ;
            }
        }
    }
}
